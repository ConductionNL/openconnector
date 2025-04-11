<?php

namespace OCA\OpenConnector\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class BaseMapper
 *
 * This class serves as the base mapper for all entity mappers in the application.
 * It provides common database operations and functionality shared across all mappers.
 *
 * @package  OCA\OpenConnector\Db
 * @template T of Entity
 */
abstract class BaseMapper extends QBMapper
{


    /**
     * Constructor
     *
     * @param IDBConnection $db        Database connection
     * @param string        $tableName Name of the database table
     */
    public function __construct(IDBConnection $db, string $tableName)
    {
        parent::__construct($db, $tableName);

    }//end __construct()


    /**
     * Find an entity by its ID
     *
     * @param  int $id The ID of the entity to find
     * @return T The found entity
     */
    public function find(int $id): Entity
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->orX(
                    $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)),
                    $qb->expr()->eq('uuid', $qb->createNamedParameter($id, IQueryBuilder::PARAM_STR))
                )
            );

        return $this->findEntity($qb);

    }//end find()


    /**
     * Find mappings by reference
     *
     * @param  string $reference The reference to search for
     * @return array Array of Mapping entities
     */
    public function findByRef(string $reference): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('reference', $qb->createNamedParameter($reference))
            );

        return $this->findEntities($qb);

    }//end findByRef()


    /**
     * Find all entities with optional filtering, pagination, and ID/UUID search
     *
     * @param  int|null   $limit            Maximum number of results to return
     * @param  int|null   $offset           Number of results to skip
     * @param  array      $filters          Additional filters to apply
     * @param  array      $searchConditions Search conditions for the query
     * @param  array      $searchParams     Parameters for the search conditions
     * @param  array|null $ids              List of IDs or UUIDs to search for
     * @return array<T> Array of found entities
     */
    public function findAll(
        ?int $limit=null,
        ?int $offset=null,
        ?array $filters=[],
        ?array $searchConditions=[],
        ?array $searchParams=[],
        ?array $ids=null
    ): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        // Apply filters
        foreach ($filters as $filter => $value) {
            if ($value === 'IS NOT NULL') {
                $qb->andWhere($qb->expr()->isNotNull($filter));
            } else if ($value === 'IS NULL') {
                $qb->andWhere($qb->expr()->isNull($filter));
            } else {
                $qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
            }
        }

        if (empty($searchConditions) === false) {
            $qb->andWhere('('.implode(' OR ', $searchConditions).')');
            foreach ($searchParams as $param => $value) {
                $qb->setParameter($param, $value);
            }
        }

        // Apply ID/UUID list search
        if (empty($ids) === false) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->in('id', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_INT_ARRAY)),
                    $qb->expr()->in('uuid', $qb->createNamedParameter($ids, IQueryBuilder::PARAM_STR_ARRAY))
                )
            );
        }

        return $this->findEntities($qb);

    }//end findAll()


    /**
     * Create a new entity from array data
     *
     * @param  array $object Array of entity data
     * @return T The created entity
     */
    public function createFromArray(array $object): Entity
    {
        $obj = $this->createEntity();
        $obj->hydrate($object);

        // Set uuid if not provided
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set version if not provided
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }

        return $this->insert($obj);

    }//end createFromArray()


    /**
     * Update an existing entity from array data
     *
     * @param  int   $id     ID of the entity to update
     * @param  array $object Array of updated entity data
     * @return T The updated entity
     */
    public function updateFromArray(int $id, array $object): Entity
    {
        $obj = $this->find($id);
        $obj->hydrate($object);

        // Set uuid if not provided
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

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

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Get the total count of all entities
     *
     * @return int The total number of entities in the database
     */
    public function getTotal(): int
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from($this->getTableName());

        $result = $qb->execute();
        $row    = $result->fetch();

        return (int) $row['count'];

    }//end getTotal()


    // @todo: fix this:
    // /**
    //  * Get the name of the database table
    //  *
    //  * @return string The table name
    //  */
    // abstract public function getTableName(): string;


    /**
     * Create a new entity instance
     *
     * @return T A new entity instance
     */
    abstract protected function createEntity(): Entity;


}//end class
