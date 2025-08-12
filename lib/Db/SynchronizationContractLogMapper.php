<?php

namespace OCA\OpenConnector\Db;

use DateInterval;
use DatePeriod;
use DateTime;
use OCA\OpenConnector\Db\SynchronizationContractLog;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ISession;
use OCP\IUserSession;
use Symfony\Component\Uid\Uuid;
use OCP\Session\Exceptions\SessionNotAvailableException;

/**
 * Class SynchronizationContractLogMapper
 *
 * Mapper class for handling SynchronizationContractLog entities
 */
class SynchronizationContractLogMapper extends QBMapper
{
	public function __construct(
		IDBConnection $db,
		private readonly IUserSession $userSession,
		private readonly ISession $session
	) {
		parent::__construct($db, 'openconnector_synchronization_contract_logs');
	}

	public function find(int $id): SynchronizationContractLog
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_synchronization_contract_logs')
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity(query: $qb);
	}

	public function findOnSynchronizationId(string $synchronizationId): ?SynchronizationContractLog
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_synchronization_contract_logs')
			->where(
				$qb->expr()->eq('synchronization_id', $qb->createNamedParameter($synchronizationId))
			);

		try {
			return $this->findEntity($qb);
		} catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
			return null;
		}
	}

	public function findAll(?int $limit = null, ?int $offset = null, ?array $filters = [], ?array $searchConditions = [], ?array $searchParams = []): array
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_synchronization_contract_logs')
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

		return $this->findEntities(query: $qb);
	}

	public function createFromArray(array $object): SynchronizationContractLog
	{
		$obj = new SynchronizationContractLog();
		$obj->hydrate($object);

		// Set uuid if not provided
		if ($obj->getUuid() === null) {
			$obj->setUuid(Uuid::v4());
		}

		// Auto-fill userId from current user session
		if ($obj->getUserId() === null && $this->userSession->getUser() !== null) {
			$obj->setUserId($this->userSession->getUser()->getUID());
		}

		// Auto-fill sessionId from current session
		if ($obj->getSessionId() === null) {
			// Try catch because we could run this from a Job and in that case have no session.
			try {
				$obj->setSessionId($this->session->getId());
			} catch (SessionNotAvailableException $exception) {
				$obj->setSessionId(null);
			}
		}

		// If no synchronizationLogId is provided, we assume that the contract is run directly from the synchronization log and set the synchronizationLogId to n.a.
		if ($obj->getSynchronizationLogId() === null) {
			$obj->setSynchronizationLogId('n.a.');
		}

		// Calculate and set size if not provided
		if ($obj->getSize() === null) {
			$obj->setSize($this->calculateLogSize($obj));
		}

		return $this->insert($obj);
	}

	public function updateFromArray(int $id, array $object): SynchronizationContractLog
	{
		$obj = $this->find($id);
		$obj->hydrate($object);

		return $this->update($obj);
	}

	/**
	 * Get synchronization execution counts by date for a specific date range
	 *
	 * @param DateTime $from Start date
	 * @param DateTime $to End date
	 *
	 * @return array Array of daily execution counts
	 * @throws Exception
	 */
	public function getSyncStatsByDateRange(DateTime $from, DateTime $to): array
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select(
				$qb->createFunction('DATE(created) as date'),
				$qb->createFunction('COUNT(*) as executions')
			)
			->from('openconnector_synchronization_contract_logs')
			->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
			->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
			->groupBy('date')
			->orderBy('date', 'ASC');

		$result = $qb->execute();
		$stats = [];

		// Create DatePeriod to iterate through all dates
		$period = new DatePeriod(
			$from,
			new DateInterval('P1D'),
			$to->modify('+1 day')
		);

		// Initialize all dates with zero values
		foreach ($period as $date) {
			$dateStr = $date->format('Y-m-d');
			$stats[$dateStr] = 0;
		}

		// Fill in actual values where they exist
		while ($row = $result->fetch()) {
			$stats[$row['date']] = (int)$row['executions'];
		}

		return $stats;
	}

	/**
	 * Get synchronization execution counts by hour for a specific date range
	 *
	 * @param DateTime $from Start date
	 * @param DateTime $to End date
	 * 
	 * @return array Array of hourly execution counts
	 * @throws Exception
	 */
	public function getSyncStatsByHourRange(DateTime $from, DateTime $to): array
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select(
				$qb->createFunction('HOUR(created) as hour'),
				$qb->createFunction('COUNT(*) as executions')
			)
			->from('openconnector_synchronization_contract_logs')
			->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
			->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
			->groupBy('hour')
			->orderBy('hour', 'ASC');

		$result = $qb->execute();
		$stats = [];

		while ($row = $result->fetch()) {
			$stats[$row['hour']] = (int)$row['executions'];
		}

		        return $stats;
    }

	/**
	 * Calculate the approximate size of a synchronization contract log entry.
	 *
	 * This method estimates the size by summing the length of all text and JSON fields.
	 *
	 * @param SynchronizationContractLog $log The log entry to calculate size for
	 *
	 * @return int The estimated size in bytes
	 */
	private function calculateLogSize(SynchronizationContractLog $log): int
	{
		$size = 0;
		
		// Add size of string fields
		$size += strlen($log->getUuid() ?? '');
		$size += strlen($log->getMessage() ?? '');
		$size += strlen($log->getSynchronizationId() ?? '');
		$size += strlen($log->getSynchronizationContractId() ?? '');
		$size += strlen($log->getSynchronizationLogId() ?? '');
		$size += strlen($log->getTargetResult() ?? '');
		$size += strlen($log->getUserId() ?? '');
		$size += strlen($log->getSessionId() ?? '');
		
		// Add size of JSON fields (source and target arrays)
		$source = $log->getSource();
		if (!empty($source)) {
			$size += strlen(json_encode($source));
		}
		
		$target = $log->getTarget();
		if (!empty($target)) {
			$size += strlen(json_encode($target));
		}
		
		// Add approximate size of other fields (booleans, dates)
		$size += 50; // Rough estimate for booleans and datetime fields
		
		return $size;
	}//end calculateLogSize()

	/**
	 * Count synchronization contract logs with optional filters.
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
			->from('openconnector_synchronization_contract_logs');

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
	 * Calculate total size of synchronization contract logs with optional filters.
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
			->from('openconnector_synchronization_contract_logs');

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
	 * This method deletes all synchronization contract logs that have expired 
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
			$qb->delete('openconnector_synchronization_contract_logs')
			   ->where($qb->expr()->isNotNull('expires'))
			   ->andWhere($qb->expr()->lt('expires', $qb->createFunction('NOW()')));

			// Execute the query and get the number of affected rows
			$result = $qb->executeStatement();

			// Return true if any rows were affected (i.e., any logs were deleted)
			return $result > 0;
		} catch (\Exception $e) {
			// Log the error for debugging purposes
			\OC::$server->getLogger()->error('Failed to clear expired synchronization contract logs: ' . $e->getMessage(), [
				'app' => 'openconnector',
				'exception' => $e
			]);
			
			// Re-throw the exception so the caller knows something went wrong
			throw $e;
		}
	}//end clearLogs()

	/**
	 * Set expiry dates for synchronization contract logs based on retention period in milliseconds.
	 *
	 * Updates the expires column for synchronization contract logs based on their creation date plus the retention period.
	 * Only affects synchronization contract logs that don't already have an expiry date set.
	 *
	 * @param int $retentionMs Retention period in milliseconds
	 *
	 * @return int Number of synchronization contract logs updated
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
			
			// Update synchronization contract logs that don't have an expiry date set
			$qb->update('openconnector_synchronization_contract_logs')
			   ->set('expires', $qb->createFunction(
				   sprintf('DATE_ADD(created, INTERVAL %d SECOND)', $retentionSeconds)
			   ))
			   ->where($qb->expr()->isNull('expires'));
			
			// Execute the update and return number of affected rows
			return $qb->executeStatement();
		} catch (\Exception $e) {
			// Log the error for debugging purposes
			\OC::$server->getLogger()->error('Failed to set expiry dates for synchronization contract logs: ' . $e->getMessage(), [
				'app' => 'openconnector',
				'exception' => $e
			]);
			
			// Re-throw the exception so the caller knows something went wrong
			throw $e;
		}
	}//end setExpiryDate()
}
