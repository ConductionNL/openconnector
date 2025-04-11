<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\SynchronizationContract;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;
/**
 * Mapper class for SynchronizationContract entities
 *
 * This class handles database operations for synchronization contracts including
 * CRUD operations and specialized queries.
 *
 * @package OCA\OpenConnector\Db
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SynchronizationContractMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for synchronization contracts
     */
    private const TABLE_NAME = 'openconnector_synchronization_contracts';

    /**
     * Constructor for SynchronizationContractMapper
     *
     * @param IDBConnection $db Database connection instance
     */
    public function __construct(IDBConnection $db)
    {
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
     * Create a new SynchronizationContract entity instance
     *
     * @return SynchronizationContract A new SynchronizationContract instance
     */
    protected function createEntity(): Entity
    {
        return new SynchronizationContract();
    }

	/**
	 * Find a synchronization contract by synchronization ID and origin ID
	 *
	 * @param string $synchronizationId The synchronization ID
	 * @param string $originId The origin ID
	 *
	 * @return SynchronizationContract|null The found contract or null if not found
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
    public function findSyncContractByOriginId(string $synchronizationId, string $originId): ?SynchronizationContract
    {
        // Create query builder
        $qb = $this->db->getQueryBuilder();

        // Build select query with synchronization and origin ID filters
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq('synchronization_id', $qb->createNamedParameter($synchronizationId))
            )
            ->andWhere(
                $qb->expr()->eq('origin_id', $qb->createNamedParameter($originId))
            );

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Find a synchronization contract by synchronization ID and target ID
     *
     * @param string $synchronization The synchronization ID
     * @param string $targetId The target ID
     * @return SynchronizationContract|bool|null The found contract, false, or null if not found
     */
    public function findOnTarget(string $synchronization, string $targetId): SynchronizationContract|bool|null
    {
        // Create query builder
        $qb = $this->db->getQueryBuilder();

        // Build select query with synchronization and target ID filters
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq('synchronization_id', $qb->createNamedParameter($synchronization))
            )
            ->andWhere(
                $qb->expr()->eq('target_id', $qb->createNamedParameter($targetId))
            );

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Find a synchronization contract by origin ID and target ID
     *
     * @param string $originId The origin ID
     * @param string $targetId The target ID
     * @return SynchronizationContract|bool|null The found contract, false, or null if not found
     */
    public function findByOriginAndTarget(string $originId, string $targetId): SynchronizationContract|bool|null
    {
        // Create query builder
        $qb = $this->db->getQueryBuilder();

        // Build select query with synchronization and target ID filters
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq('origin_id', $qb->createNamedParameter($originId))
            )
            ->andWhere(
                $qb->expr()->eq('target_id', $qb->createNamedParameter($targetId))
            );

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Find all synchronization contracts by synchronization ID and where target have the given schema id
     *
     * @param string $synchronization The synchronization ID
     *
     * @return array An array of target IDs or an empty array if none found
     */
    public function findAllBySynchronizationAndSchema(string $synchronizationId, string $schemaId): array
    {
        // Create query builder
        $qb = $this->db->getQueryBuilder();

        // Build select query with synchronization ID and schema filter
        $qb->select('c.*')
            ->from(self::TABLE_NAME, 'c')
            ->innerJoin(
                'c',
                'oc_openregister_objects',
                'o',
                $qb->expr()->eq('c.target_id', 'o.uuid')
            )
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('c.synchronization_id', $qb->createNamedParameter($synchronizationId)),
                    $qb->expr()->eq('o.schema', $qb->createNamedParameter($schemaId))
                )
            );

        try {
            return $this->findEntities($qb);
        } catch (\Exception $e) {
            return [];
        }
    }
    

    /**
     * Create a new synchronization contract from array data
     *
     * @param array $object Array of contract data
     * @return SynchronizationContract The created contract entity
     */
    public function createFromArray(array $object): SynchronizationContract
    {
        // Create and hydrate new contract object
        $obj = new SynchronizationContract();
        $obj->hydrate(object: $object);

        // Set uuid
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set version
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }

        return $this->insert(entity: $obj);
    }

 

    /**
     * Find a synchronization contract by origin ID.
     *
     * @param string $originId The origin ID to search for.
     *
     * @return SynchronizationContract|null The matching contract or null if not found.
     */
    public function findByOriginId(string $originId): ?SynchronizationContract
    {
        // Create query builder
        $qb = $this->db->getQueryBuilder();

        // Build query to find contract matching origin_id
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq('origin_id', $qb->createNamedParameter($originId))
            )
            ->setMaxResults(1); // Ensure only one result is returned

        try {
            return $this->findEntity($qb); // Use findEntity to return a single result
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null; // Return null if no match is found
        }
    }

    /**
     * Find a synchronization contract by target ID.
     *
     * @param string $targetId The target ID to search for.
     *
     * @return SynchronizationContract[] The matching contract or null if not found.
     */
    public function findByTargetId(string $targetId): array
    {
        // Create query builder
        $qb = $this->db->getQueryBuilder();

        // Build query to find contract matching origin_id
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq('target_id', $qb->createNamedParameter($targetId))
            ); // Ensure only one result is returned

        try {
            return $this->findEntities($qb); // Use findEntity to return a single result
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return []; // Return null if no match is found
        }
    }


    /**
     * Find synchronization contracts by type and ID
     *
     * @param string $type The type to search for (e.g., 'user', 'group')
     * @param string $id The ID to search for
     * @return array<SynchronizationContract> Array of matching contracts
     */
    public function findByTypeAndId(string $type, string $id): array
    {
        // Create query builder
        $qb = $this->db->getQueryBuilder();

        // Build query to find contracts matching type/id as either source or target
        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->andX(
                        $qb->expr()->eq('source_type', $qb->createNamedParameter($type)),
                        $qb->expr()->eq('origin_id', $qb->createNamedParameter($id))
                    ),
                    $qb->expr()->andX(
                        $qb->expr()->eq('target_type', $qb->createNamedParameter($type)),
                        $qb->expr()->eq('target_id', $qb->createNamedParameter($id))
                    )
                )
            );

        return $this->findEntities($qb);
    }

    /**
     * Handle object removal by updating or removing associated contracts
     *
     * This method finds all contracts associated with the given object identifier,
     * clears the appropriate fields (origin or target) and deletes contracts that
     * have no remaining associations.
     *
     * @param string $objectIdentifier The ID of the removed object
     * @return array
     * @throws Exception If there is an error handling the object removal
     */
    public function handleObjectRemoval(string $objectIdentifier): array
    {
        try {
            // Find contracts where object ID matches either origin or target
            $qb = $this->db->getQueryBuilder();
            $qb->select('*')
               ->from(self::TABLE_NAME)
               ->where(
                   $qb->expr()->orX(
                       $qb->expr()->eq('origin_id', $qb->createNamedParameter($objectIdentifier)),
                       $qb->expr()->eq('target_id', $qb->createNamedParameter($objectIdentifier))
                   )
               );

            $contracts = $this->findEntities($qb);

            foreach ($contracts as $contract) {
                // Clear origin fields if object was the source
                if ($contract->getOriginId() === $objectIdentifier) {
                    $contract->setOriginId(null);
                    $contract->setOriginHash(null);
                    $this->update($contract);
                }

                // Clear target fields if object was the target
                if ($contract->getTargetId() === $objectIdentifier) {
                    $contract->setTargetId(null);
                    $contract->setTargetHash(null);
                    $this->update($contract);
                }

                // Delete contract if no remaining associations
                if ($contract->getOriginId() === null && $contract->getTargetId() === null) {
                    $this->delete($contract);
                }
            }
			return $contracts;

        } catch (Exception $e) {
            throw new Exception('Failed to handle object removal: ' . $e->getMessage());
        }
    }

    /**
     * Find all contracts with optional filtering and pagination
     *
     * @param int|null $limit Maximum number of results to return
     * @param int|null $offset Number of results to skip
     * @param array|null $filters Associative array of filter conditions (column => value)
     * @param array|null $searchConditions Search conditions for the query
     * @param array|null $searchParams Parameters for the search conditions
     * @param array|null $ids List of IDs or UUIDs to search for
     * @return array<SynchronizationContract> Array of matching contract entities
     */
    public function findAll(
        ?int $limit=null,
        ?int $offset=null,
        ?array $filters=[],
        ?array $searchConditions=[],
        ?array $searchParams=[],
        ?array $ids=null
    ): array {
        return parent::findAll($limit, $offset, $filters, $searchConditions, $searchParams, $ids);
    }
}
