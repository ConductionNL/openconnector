<?php

namespace OCA\OpenConnector\Db;

use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class EventSubscriptionMapper
 *
 * Handles database operations for event subscriptions
 *
 * @package OCA\OpenConnector\Db
 */
class EventSubscriptionMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for event subscriptions
     */
    private const TABLE_NAME = 'openconnector_event_subscriptions';


    /**
     * Constructor
     *
     * @param IDBConnection $db Database connection
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
     * Create a new EventSubscription entity instance
     *
     * @return EventSubscription A new EventSubscription instance
     */
    protected function createEntity(): Entity
    {
        return new EventSubscription();
    }
    
    /**
     * Find all event subscriptions with optional filtering and pagination
     *
     * @param int|null $limit Maximum number of results to return
     * @param int|null $offset Number of results to skip
     * @param array|null $filters Associative array of filter conditions (column => value)
     * @param array|null $searchConditions Search conditions for the query
     * @param array|null $searchParams Parameters for the search conditions
     * @param array|null $ids List of IDs or UUIDs to search for
     * @return array<EventSubscription> Array of matching event subscription entities
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
