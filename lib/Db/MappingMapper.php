<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\Mapping;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class MappingMapper
 *
 * This class is responsible for mapping Mapping entities to the database.
 * It provides methods for finding, creating, and updating Mapping objects.
 *
 * @package OCA\OpenConnector\Db
 * @extends BaseMapper<Mapping>
 */
class MappingMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for mappings
     */
    private const TABLE_NAME = 'openconnector_mappings';


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

    }//end getTableName()


    /**
     * Create a new Mapping entity instance
     *
     * @return Mapping A new Mapping instance
     */
    protected function createEntity(): Entity
    {
        return new Mapping();

    }//end createEntity()

    /**
     * Find a mapping by ID
     *
     * @param int $id The ID of the mapping to find
     * @return Mapping The found mapping entity
     * @throws DoesNotExistException If the mapping doesn't exist
     * @throws MultipleObjectsReturnedException If multiple mappings match the criteria
     */
    public function find(int $id): Mapping
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }//end find()

}//end class
