<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ISession;
use OCP\IUserSession;
use Symfony\Component\Uid\Uuid;
use OCP\Session\Exceptions\SessionNotAvailableException;

class SynchronizationLogMapper extends QBMapper
{
	public function __construct(
		IDBConnection $db,
		private readonly IUserSession $userSession,
		private readonly ISession $session
	) {
		parent::__construct($db, 'openconnector_synchronization_logs');
	}

	public function find(int $id): SynchronizationLog
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_synchronization_logs')
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity($qb);
	}

	public function findAll(
		?int $limit = null, 
		?int $offset = null, 
		?array $filters = [], 
		?array $searchConditions = [], 
		?array $searchParams = []
	): array {
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_synchronization_logs')
			->orderBy('created', 'DESC')
			->setMaxResults($limit)
			->setFirstResult($offset);

		foreach ($filters as $filter => $value) {
			if ($value === 'IS NOT NULL') {
				$qb->andWhere($qb->expr()->isNotNull($filter));
			} elseif ($value === 'IS NULL') {
				$qb->andWhere($qb->expr()->isNull($filter));
			} else {
				$qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
			}
		}

		if (empty($searchConditions) === false) {
			$qb->andWhere('(' . implode(' OR ', $searchConditions) . ')');
			foreach ($searchParams as $param => $value) {
				$qb->setParameter($param, $value);
			}
		}

		return $this->findEntities($qb);
	}

	/**
	 * Process contracts array to ensure it only contains valid UUIDs
	 *
	 * @param array $contracts Array of contracts or contract objects
	 * @return array Processed array containing only valid UUIDs
	 */
	private function processContracts(array $contracts): array 
	{
		return array_values(array_filter(
			array_map(
				function ($contract) {
					if (is_object($contract)) {
						// If it's an object with getUuid method, use that
						if (method_exists($contract, 'getUuid')) {
							return $contract->getUuid() ?: null;
						}
						return null;
					}
					// If it's already a string (UUID), return it
					return is_string($contract) ? $contract : null;
				},
				$contracts
			)
		));
	}

	/**
	 * Creates a new synchronization log entry
	 *
	 * @param array $object The log data
	 * @return SynchronizationLog The created log entry
	 */
	public function createFromArray(array $object): SynchronizationLog
	{
		$obj = new SynchronizationLog();

		// Auto-fill system fields
		$object['uuid'] = $object['uuid'] ?? Uuid::v4();
		$object['userId'] = $object['userId'] ?? $this->userSession->getUser()?->getUID();

		// Catch error from session, because when running from a Job this might cause an error preventing the Job from being ran.
		try {
			$object['sessionId'] = $object['sessionId'] ?? $this->session->getId();
		} catch (SessionNotAvailableException $exception) {
			$object['sessionId'] = null;
		}

		$object['created'] = $object['created'] ?? new DateTime();
		$object['expires'] = $object['expires'] ?? new DateTime('+30 days');
		$object['test'] = $object['test'] ?? false;
		$object['force'] = $object['force'] ?? false;

		// Process contracts in results if they exist
		if (isset($object['result']['contracts']) && is_array($object['result']['contracts'])) {
			$object['result']['contracts'] = $this->processContracts($object['result']['contracts']);
		}

		$obj->hydrate($object);

		// Set uuid
		if ($obj->getUuid() === null){
			$obj->setUuid(Uuid::v4());
		}

		// Calculate and set size if not provided
		if ($obj->getSize() === null) {
			$obj->setSize($this->calculateLogSize($obj));
		}

		return $this->insert($obj);
	}

	/**
	 * Updates an existing synchronization log entry
	 *
	 * @param int $id The ID of the log entry to update
	 * @param array $object The updated log data
	 * @return SynchronizationLog The updated log entry
	 */
	public function updateFromArray(int $id, array $object): SynchronizationLog
	{
		$obj = $this->find($id);
		
		// Process contracts in results if they exist
		if (isset($object['result']['contracts']) && is_array($object['result']['contracts'])) {
			$object['result']['contracts'] = $this->processContracts($object['result']['contracts']);
		}
		
		$obj->hydrate($object);

		return $this->update($obj);
	}

	/**
	 * Get the total count of synchronization logs
	 *
	 * @param array $filters Optional filters to apply
	 * @return int The total number of logs
	 * @throws \OCP\DB\Exception Database operation exceptions
	 *
	 * @psalm-return int
	 * @phpstan-return int
	 */
	public function getTotalCount(array $filters = []): int
	{
		$qb = $this->db->getQueryBuilder();

		// Select count of all logs
		$qb->select($qb->createFunction('COUNT(*) as count'))
		   ->from('openconnector_synchronization_logs');

		// Apply filters if provided
		foreach ($filters as $filter => $value) {
			if ($value === 'IS NOT NULL') {
				$qb->andWhere($qb->expr()->isNotNull($filter));
			} elseif ($value === 'IS NULL') {
				$qb->andWhere($qb->expr()->isNull($filter));
			} else {
				$qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
			}
		}

		$result = $qb->execute();
		$row = $result->fetch();

		// Return the total count
		return (int)$row['count'];
	}

	/**
	 * Calculate the approximate size of a synchronization log entry.
	 *
	 * This method estimates the size by summing the length of all text and JSON fields.
	 *
	 * @param SynchronizationLog $log The log entry to calculate size for
	 *
	 * @return int The estimated size in bytes
	 */
	private function calculateLogSize(SynchronizationLog $log): int
	{
		$size = 0;
		
		// Add size of string fields
		$size += strlen($log->getUuid() ?? '');
		$size += strlen($log->getMessage() ?? '');
		$size += strlen($log->getSynchronizationId() ?? '');
		$size += strlen($log->getUserId() ?? '');
		$size += strlen($log->getSessionId() ?? '');
		
		// Add size of JSON fields (result array)
		$result = $log->getResult();
		if (!empty($result)) {
			$size += strlen(json_encode($result));
		}
		
		// Add approximate size of other fields (execution time, booleans, dates)
		$size += 50; // Rough estimate for integers, booleans, and datetime fields
		
		return $size;
	}//end calculateLogSize()

	/**
	 * Count synchronization logs with optional filters.
	 *
	 * This method provides flexible filtering capabilities similar to findAll
	 * but returns only the count of matching records.
	 *
	 * @param array $filters Optional filters to apply
	 *
	 * @return int The count of logs matching the filters
	 *
	 * @throws \OCP\DB\Exception Database operation exceptions
	 */
	public function count(array $filters = []): int
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->count('*'))
			->from('openconnector_synchronization_logs');

		// Apply filters
		foreach ($filters as $filter => $value) {
			if ($value === 'IS NOT NULL') {
				$qb->andWhere($qb->expr()->isNotNull($filter));
			} elseif ($value === 'IS NULL') {
				$qb->andWhere($qb->expr()->isNull($filter));
			} elseif (is_array($value)) {
				// Handle array values like ['IS NULL', '']
				$conditions = [];
				foreach ($value as $val) {
					if ($val === 'IS NULL') {
						$conditions[] = $qb->expr()->isNull($filter);
					} elseif ($val === 'IS NOT NULL') {
						$conditions[] = $qb->expr()->isNotNull($filter);
					} else {
						$conditions[] = $qb->expr()->eq($filter, $qb->createNamedParameter($val));
					}
				}
				if (!empty($conditions)) {
					$qb->andWhere($qb->expr()->orX(...$conditions));
				}
			} else {
				$qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
			}
		}

		$result = $qb->executeQuery();
		$row = $result->fetch();

		return (int)($row['COUNT(*)'] ?? 0);
	}//end count()

	/**
	 * Calculate total size of synchronization logs with optional filters.
	 *
	 * This method sums the size field of logs matching the given filters,
	 * useful for storage management and retention analysis.
	 *
	 * @param array $filters Optional filters to apply
	 *
	 * @return int The total size of logs matching the filters in bytes
	 *
	 * @throws \OCP\DB\Exception Database operation exceptions
	 */
	public function size(array $filters = []): int
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->sum('size'))
			->from('openconnector_synchronization_logs');

		// Apply filters
		foreach ($filters as $filter => $value) {
			if ($value === 'IS NOT NULL') {
				$qb->andWhere($qb->expr()->isNotNull($filter));
			} elseif ($value === 'IS NULL') {
				$qb->andWhere($qb->expr()->isNull($filter));
			} elseif (is_array($value)) {
				// Handle array values like ['IS NULL', '']
				$conditions = [];
				foreach ($value as $val) {
					if ($val === 'IS NULL') {
						$conditions[] = $qb->expr()->isNull($filter);
					} elseif ($val === 'IS NOT NULL') {
						$conditions[] = $qb->expr()->isNotNull($filter);
					} else {
						$conditions[] = $qb->expr()->eq($filter, $qb->createNamedParameter($val));
					}
				}
				if (!empty($conditions)) {
					$qb->andWhere($qb->expr()->orX(...$conditions));
				}
			} else {
				$qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
			}
		}

		$result = $qb->executeQuery();
		$row = $result->fetch();

		return (int)($row['SUM(size)'] ?? 0);
	}//end size()

	/**
	 * Clear expired logs from the database.
	 *
	 * This method deletes all synchronization logs that have expired 
	 * (i.e., their 'expires' date is earlier than the current date and time)
	 * and have the 'expires' column set. This helps maintain database performance 
	 * by removing old log entries that are no longer needed.
	 *
	 * @return bool True if any logs were deleted, false otherwise.
	 *
	 * @throws \Exception Database operation exceptions
	 */
	public function clearLogs(): bool
	{
		try {
			// Get the query builder for database operations
			$qb = $this->db->getQueryBuilder();

			// Build the delete query to remove expired logs that have the 'expires' column set
			$qb->delete('openconnector_synchronization_logs')
			   ->where($qb->expr()->isNotNull('expires'))
			   ->andWhere($qb->expr()->lt('expires', $qb->createFunction('NOW()')));

			// Execute the query and get the number of affected rows
			$result = $qb->executeStatement();

			// Return true if any rows were affected (i.e., any logs were deleted)
			return $result > 0;
		} catch (\Exception $e) {
			// Log the error for debugging purposes
			\OC::$server->getLogger()->error('Failed to clear expired synchronization logs: ' . $e->getMessage(), [
				'app' => 'openconnector',
				'exception' => $e
			]);
			
			// Re-throw the exception so the caller knows something went wrong
			throw $e;
		}
	}//end clearLogs()

	/**
	 * Cleans up expired log entries
	 *
	 * @deprecated Use clearLogs() instead for consistency
	 * @return int Number of deleted entries
	 */
	public function cleanupExpired(): int
	{
		$qb = $this->db->getQueryBuilder();

		$qb->delete('openconnector_synchronization_logs')
			->where($qb->expr()->lt('expires', $qb->createNamedParameter(new DateTime(), IQueryBuilder::PARAM_DATE)));

		return $qb->executeStatement();
	}

	/**
	 * Set expiry dates for synchronization logs based on retention period in milliseconds.
	 *
	 * Updates the expires column for synchronization logs based on their creation date plus the retention period.
	 * Only affects synchronization logs that don't already have an expiry date set.
	 *
	 * @param int $retentionMs Retention period in milliseconds
	 *
	 * @return int Number of synchronization logs updated
	 *
	 * @throws \Exception Database operation exceptions
	 *
	 * @psalm-return int
	 * @phpstan-return int
	 */
	public function setExpiryDate(int $retentionMs): int
	{
		try {
			// Convert milliseconds to seconds for DateTime calculation
			$retentionSeconds = intval($retentionMs / 1000);
			
			// Get the query builder
			$qb = $this->db->getQueryBuilder();
			
			// Update synchronization logs that don't have an expiry date set
			$qb->update('openconnector_synchronization_logs')
			   ->set('expires', $qb->createFunction(
				   sprintf('DATE_ADD(created, INTERVAL %d SECOND)', $retentionSeconds)
			   ))
			   ->where($qb->expr()->isNull('expires'));
			
			// Execute the update and return number of affected rows
			return $qb->executeStatement();
		} catch (\Exception $e) {
			// Log the error for debugging purposes
			\OC::$server->getLogger()->error('Failed to set expiry dates for synchronization logs: ' . $e->getMessage(), [
				'app' => 'openconnector',
				'exception' => $e
			]);
			
			// Re-throw the exception so the caller knows something went wrong
			throw $e;
		}
	}//end setExpiryDate()
}
