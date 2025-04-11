<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\Consumer;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\BaseMapper;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class ConsumerMapper
 *
 * This class is responsible for mapping Consumer entities to the database.
 * It provides methods for finding, creating, and updating Consumer objects.
 *
 * @package OCA\OpenConnector\Db
 */
class ConsumerMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for consumers
     */
    private const TABLE_NAME = 'openconnector_consumers';


    /**
     * ConsumerMapper constructor.
     *
     * @param IDBConnection $db The database connection
     */
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
     * Create a new Consumer entity instance
     *
     * @return Consumer A new Consumer instance
     */
    protected function createEntity(): Entity
    {
        return new Consumer();

    }//end createEntity()


}//end class
