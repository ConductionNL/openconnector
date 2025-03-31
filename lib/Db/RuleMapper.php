<?php
/**
 * OpenConnector - Connect your Nextcloud to external services
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @category  Mapper
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class RuleMapper
 *
 * Handles database operations for rules
 */
class RuleMapper extends QBMapper
{


    /**
     * Constructor
     *
     * @param         IDBConnection $db Database connection
     * @psalm-param   IDBConnection $db
     * @phpstan-param IDBConnection $db
     * @return        void
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_rules');

    }//end __construct()


    /**
     * Find a rule by ID
     *
     * @param         int $id Rule ID
     * @psalm-param   int $id
     * @phpstan-param int $id
     * @return        Rule The found rule
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the rule doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple rules match
     */
    public function find(int $id): Rule
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_rules')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);

    }//end find()


    /**
     * Find a rule by reference
     *
     * @param          string $reference Rule reference
     * @psalm-param    string $reference
     * @phpstan-param  string $reference
     * @return         Rule[] Array of rules matching the reference
     * @psalm-return   array<int, Rule>
     * @phpstan-return array<int, Rule>
     */
    public function findByRef(string $reference): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_rules')
            ->where(
                $qb->expr()->eq('reference', $qb->createNamedParameter($reference))
            );

        return $this->findEntities(query: $qb);

    }//end findByRef()


    /**
     * Find all rules with optional filtering
     *
     * @param          int|null   $limit            Maximum number of results
     * @param          int|null   $offset           Number of records to skip
     * @param          array|null $filters          Key-value pairs for filtering
     * @param          array|null $searchConditions Search conditions
     * @param          array|null $searchParams     Search parameters
     * @psalm-param    int|null $limit
     * @psalm-param    int|null $offset
     * @psalm-param    array<string, mixed>|null $filters
     * @psalm-param    array<int, string>|null $searchConditions
     * @psalm-param    array<string, mixed>|null $searchParams
     * @phpstan-param  int|null $limit
     * @phpstan-param  int|null $offset
     * @phpstan-param  array<string, mixed>|null $filters
     * @phpstan-param  array<int, string>|null $searchConditions
     * @phpstan-param  array<string, mixed>|null $searchParams
     * @return         Rule[] Array of rules
     * @psalm-return   array<int, Rule>
     * @phpstan-return array<int, Rule>
     */
    public function findAll(
        ?int $limit=null,
        ?int $offset=null,
        ?array $filters=[],
        ?array $searchConditions=[],
        ?array $searchParams=[]
    ): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_rules')
            ->orderBy('order', 'ASC')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

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

        return $this->findEntities($qb);

    }//end findAll()


    /**
     * Create a new rule from array data
     *
     * @param         array $object Rule data
     * @psalm-param   array<string, mixed> $object
     * @phpstan-param array<string, mixed> $object
     * @return        Rule The created rule
     */
    public function createFromArray(array $object): Rule
    {
        $obj = new Rule();
        $obj->hydrate($object);

        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set version.
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }

        // Rule-specific logic.
        // If no order is specified, append to the end.
        if ($obj->getOrder() === null) {
            $maxOrder = $this->getMaxOrder();
            $obj->setOrder($maxOrder + 1);
        }

        return $this->insert(entity: $obj);

    }//end createFromArray()


    /**
     * Update a rule from array data
     *
     * @param         int   $id     Rule ID
     * @param         array $object Updated rule data
     * @psalm-param   int $id
     * @psalm-param   array<string, mixed> $object
     * @phpstan-param int $id
     * @phpstan-param array<string, mixed> $object
     * @return        Rule The updated rule
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the rule doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple rules match
     */
    public function updateFromArray(int $id, array $object): Rule
    {
        $obj = $this->find($id);

        // Set version.
        if (empty($obj->getVersion()) === true) {
            $object['version'] = '0.0.1';
        } else if (empty($object['version']) === true) {
            // Update version.
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
     * Get the highest order number for rules
     *
     * @return int The maximum order value
     */
    private function getMaxOrder(): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->createFunction('COALESCE(MAX(`order`), 0) as max_order'))
            ->from('openconnector_rules');

        $result = $qb->execute();
        $row    = $result->fetch();
        $result->closeCursor();

        return (int) ($row['max_order']);

    }//end getMaxOrder()


    /**
     * Get the total count of all rules
     *
     * @return int The total number of rules in the database
     */
    public function getTotalCount(): int
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_rules');

        $result = $qb->execute();
        $row    = $result->fetch();

        return (int) $row['count'];

    }//end getTotalCount()


    /**
     * Reorder rules
     *
     * @param         array $orderMap Array of rule ID => new order
     * @psalm-param   array<int, int> $orderMap
     * @phpstan-param array<int, int> $orderMap
     * @return        void
     */
    public function reorder(array $orderMap): void
    {
        foreach ($orderMap as $ruleId => $newOrder) {
            $qb = $this->db->getQueryBuilder();
            $qb->update('openconnector_rules')
                ->set('order', $qb->createNamedParameter($newOrder, IQueryBuilder::PARAM_INT))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($ruleId, IQueryBuilder::PARAM_INT)))
                ->execute();
        }

    }//end reorder()


}//end class
