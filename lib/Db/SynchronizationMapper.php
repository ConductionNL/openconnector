<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\Synchronization;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class SynchronizationMapper
 *
 * This class is responsible for mapping Synchronization entities to the database.
 * It provides methods for finding, creating, and updating Synchronization objects.
 *
 * @package OCA\OpenConnector\Db
 * @extends BaseMapper<Synchronization>
 */
class SynchronizationMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for synchronizations
     */
    private const TABLE_NAME = 'openconnector_synchronizations';


    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, self::TABLE_NAME);

    }//end __construct()

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
     * Create a new Synchronization entity instance
     *
     * @return Synchronization A new Synchronization instance
     */
    protected function createEntity(): Entity
    {
        return new Synchronization();
    }

    public function find(int $id): Synchronization
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity(query: $qb);

    }//end find()


    public function findByRef(string $reference): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq('reference', $qb->createNamedParameter($reference))
            );

        return $this->findEntities(query: $qb);

    }//end findByRef()


    /**
     * Find a synchronization by UUID
     *
     * @param string $uuid The UUID of the synchronization to find
     * @return Synchronization The found synchronization entity
     * @throws DoesNotExistException If the synchronization doesn't exist
     * @throws MultipleObjectsReturnedException If multiple synchronizations match the criteria
     */
    public function findByUuid(string $uuid): Synchronization
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->eq('uuid', $qb->createNamedParameter($uuid))
            );

        return $this->findEntity(query: $qb);
    }


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


    public function createFromArray(array $object): Synchronization
    {
        $obj = new Synchronization();
        $obj->hydrate($object);

        // Set uuid
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set version
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }

        return $this->insert(entity: $obj);

    }//end createFromArray()


    public function updateFromArray(int $id, array $object): Synchronization
    {
        $obj = $this->find($id);

        // Set version
        if (empty($obj->getVersion()) === true) {
            $object['version'] = '0.0.1';
        } else if (empty($object['version']) === true) {
            // Update version
            $version = explode('.', $obj->getVersion());
            if (isset($version[2]) === true) {
                $version[2]        = ((int) $version[2] + 1);
                $object['version'] = implode('.', $version);
            }
        }

        $obj->hydrate($object);

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Find synchronizations that are linked to a specific register
     *
     * @param int $registerId The ID of the register to find synchronizations for
     *
     * @return array<Synchronization> Array of Synchronization entities linked to the register
     */
    public function getByRegister(int $registerId): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('target_type', $qb->createNamedParameter('register/schema')),
                    // Use LIKE to match the part before the '/' in target_id
                    $qb->expr()->like('target_id', $qb->createNamedParameter($registerId . '/%'))
                )
            );

        return $this->findEntities(query: $qb);

    }//end getByRegister()


}//end class
