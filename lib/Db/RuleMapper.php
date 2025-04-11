<?php

namespace OCA\OpenConnector\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class RuleMapper
 *
 * Handles database operations for rules
 *
 * @package OCA\OpenConnector\Db
 * @extends BaseMapper<Rule>
 */
class RuleMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for rules
     */
    private const TABLE_NAME = 'openconnector_rules';


    /**
     * @param IDBConnection $db
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
     * Create a new Rule entity instance
     *
     * @return Rule A new Rule instance
     */
    protected function createEntity(): Entity
    {
        return new Rule();

    }//end createEntity()


    /**
     * Find a rule by ID
     *
     * @param int $id The ID of the rule to find
     * @return Rule The found rule entity
     * @throws DoesNotExistException If the rule doesn't exist
     * @throws MultipleObjectsReturnedException If multiple rules match the criteria
     */
    public function find(int $id): Rule
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    /**
     * Find a rule by UUID
     *
     * @param string $uuid The UUID of the rule to find
     * @return Rule The found rule entity
     * @throws DoesNotExistException If the rule doesn't exist
     * @throws MultipleObjectsReturnedException If multiple rules match the criteria
     */
    public function findByUuid(string $uuid): Rule
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('uuid', $qb->createNamedParameter($uuid))
            );

        return $this->findEntity($qb);
    }

    /**
     * Find all rules with optional filtering and pagination
     *
     * @param int|null $limit Maximum number of results to return
     * @param int|null $offset Number of results to skip
     * @param array|null $filters Associative array of filter conditions (column => value)
     * @param array|null $searchConditions Search conditions for the query
     * @param array|null $searchParams Parameters for the search conditions
     * @param array|null $ids List of IDs or UUIDs to search for
     * @return Rule[] Array of matching rule entities
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

    /**
     * Create a new rule from array data
     *
     * @param  array<string,mixed> $object
     * @return Rule
     */
    public function createFromArray(array $object): Rule
    {
        // Create and hydrate new rule object
        $obj = new Rule();
        $obj->hydrate($object);
        
        // Set uuid if not provided
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }
        
        // Set version if not provided
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }
        
        $obj = $this->insert($obj);

        // Rule-specific logic
        // If no order is specified, append to the end
        if ($obj->getOrder() === null) {
            $maxOrder = $this->getMaxOrder();
            $obj->setOrder($maxOrder + 1);
            $this->update($obj);
        }

        return $obj;

    }//end createFromArray()


    /**
     * Get the highest order number for rules
     *
     * @return int
     */
    private function getMaxOrder(): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->createFunction('COALESCE(MAX(`order`), 0) as max_order'))
            ->from($this->getTableName());

        $result = $qb->execute();
        $row    = $result->fetch();
        $result->closeCursor();

        return (int) ($row['max_order']);

    }//end getMaxOrder()


    /**
     * Reorder rules
     *
     * @param  array<int,int> $orderMap Array of rule ID => new order
     * @return void
     */
    public function reorder(array $orderMap): void
    {
        foreach ($orderMap as $ruleId => $newOrder) {
            $qb = $this->db->getQueryBuilder();
            $qb->update($this->getTableName())
                ->set('order', $qb->createNamedParameter($newOrder, IQueryBuilder::PARAM_INT))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($ruleId, IQueryBuilder::PARAM_INT)))
                ->execute();
        }

    }//end reorder()


}//end class
