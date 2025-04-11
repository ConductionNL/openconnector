<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ISession;
use OCP\IUserSession;
use OCP\Session\Exceptions\SessionNotAvailableException;
use Symfony\Component\Uid\Uuid;

/**
 * Class SynchronizationLogMapper
 *
 * This class is responsible for mapping SynchronizationLog entities to the database.
 * It provides methods for finding, creating, and updating SynchronizationLog objects.
 *
 * @package OCA\OpenConnector\Db
 * @extends BaseMapper<SynchronizationLog>
 */
class SynchronizationLogMapper extends \OCA\OpenConnector\Db\BaseMapper
{
	/**
	 * The name of the database table for synchronization logs
	 */
	private const TABLE_NAME = 'openconnector_synchronization_logs';

	public function __construct(
		IDBConnection $db,
		private readonly IUserSession $userSession,
		private readonly ISession $session
	) {
		parent::__construct($db, self::TABLE_NAME);
	}

	/**
	 * Get the name of the database table
	 *
	 * @return string The table name
	 */
	public function getTableName(): string
	{
		return self::TABLE_NAME;
	}

	/**
	 * Create a new SynchronizationLog entity instance
	 *
	 * @return SynchronizationLog A new SynchronizationLog instance
	 */
	protected function createEntity(): Entity
	{
		return new SynchronizationLog();
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
	 * Clean up expired synchronization logs
	 *
	 * @return int Number of deleted entries
	 */
	public function cleanupExpired(): int
	{
		$qb = $this->db->getQueryBuilder();

		$qb->delete($this->getTableName())
			->where($qb->expr()->lt('expires', $qb->createNamedParameter(new DateTime(), IQueryBuilder::PARAM_DATE)));

		return $qb->executeStatement();
	}

	/**
	 * Find a synchronization log by ID
	 *
	 * @param int $id The ID of the synchronization log to find
	 * @return SynchronizationLog The found synchronization log entity
	 * @throws DoesNotExistException If the log doesn't exist
	 * @throws MultipleObjectsReturnedException If multiple logs match the criteria
	 */
	public function find(int $id): SynchronizationLog
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity($qb);
	}
}
