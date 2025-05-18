<?php

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
 *
 * @package OCA\OpenConnector\Db
 */
class RuleMapper extends QBMapper
{
	/**
	 * @param IDBConnection $db
	 */
	public function __construct(IDBConnection $db)
	{
		parent::__construct($db, 'openconnector_rules');
	}

	/**
	 * Find a rule by ID
	 *
	 * @param int $id
	 * @return Rule
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
	}

	/**
	 * Find a rule by reference
	 *
	 * @param int $id
	 * @return Rule
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
	}

	/**
	 * Find all rules with optional filtering
	 *
	 * @param int|null $limit
	 * @param int|null $offset
	 * @param array<string,mixed> $filters
	 * @param array<string> $searchConditions
	 * @param array<string,mixed> $searchParams
	 * @return array<Rule>
	 */
	public function findAll(?int $limit = null, ?int $offset = null, ?array $filters = [], ?array $searchConditions = [], ?array $searchParams = []): array
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_rules')
			->orderBy('order', 'ASC')
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

		return $this->findEntities($qb);
	}

	/**
	 * Create a new rule from array data
	 *
	 * @param array<string,mixed> $object
	 * @return Rule
	 */
	public function createFromArray(array $object): Rule
	{
		$obj = new Rule();
		$obj->hydrate($object);

		// Set uuid
		if ($obj->getUuid() === null) {
			$obj->setUuid(Uuid::v4());
		}

		// Set version
		if (empty($obj->getVersion()) === true) {
			$obj->setVersion('0.0.1');
		}

		// Rule-specific logic
		// If no order is specified, append to the end
		if ($obj->getOrder() === null) {
			$maxOrder = $this->getMaxOrder();
			$obj->setOrder($maxOrder + 1);
		}

		return $this->insert(entity: $obj);
	}

	/**
	 * Update a rule from array data
	 *
	 * @param int $id
	 * @param array<string,mixed> $object
	 * @return Rule
	 */
	public function updateFromArray(int $id, array $object): Rule
	{
		$obj = $this->find($id);

		// Set version
		if (empty($obj->getVersion()) === true) {
			$object['version'] = '0.0.1';
		} else if (empty($object['version']) === true) {
			// Update version
			$version = explode('.', $obj->getVersion());
			if (isset($version[2]) === true) {
				$version[2] = (int) $version[2] + 1;
				$object['version'] = implode('.', $version);
			}
		}

		$obj->hydrate($object);

		return $this->update($obj);
	}

	/**
	 * Get the highest order number for rules
	 *
	 * @return int
	 */
	private function getMaxOrder(): int
	{
		$qb = $this->db->getQueryBuilder();
		$qb->select($qb->createFunction('COALESCE(MAX(`order`), 0) as max_order'))
		   ->from('openconnector_rules');

		$result = $qb->execute();
		$row = $result->fetch();
		$result->closeCursor();

		return (int)($row['max_order']);
	}

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
		$row = $result->fetch();

		return (int)$row['count'];
	}

	/**
	 * Reorder rules
	 *
	 * @param array<int,int> $orderMap Array of rule ID => new order
	 * @return void
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
	}

	/**
	 * Find all rules that belong to a specific configuration.
	 *
	 * @param string $configurationId The ID of the configuration to find rules for
	 * @return array<Rule> Array of Rule entities
	 */
	public function findByConfiguration(string $configurationId): array
	{
		$sql = 'SELECT * FROM `' . $this->getTableName() . '` WHERE JSON_CONTAINS(configurations, ?)';
		return $this->findEntities($sql, [$configurationId]);
	}

	/**
	 * Get a map of rule IDs to their corresponding slugs
	 *
	 * @return array<string,string> Array mapping rule IDs to slugs
	 */
	public function getIdToSlugMap(): array
	{
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'slug')
		   ->from($this->getTableName());

		$result = $qb->execute();
		$map = [];
		while ($row = $result->fetch()) {
			$map[$row['id']] = $row['slug'];
		}
		$result->closeCursor();

		return $map;
	}

	/**
	 * Get a map of rule slugs to their corresponding IDs
	 *
	 * @return array<string,string> Array mapping rule slugs to IDs
	 */
	public function getSlugToIdMap(): array
	{
		$qb = $this->db->getQueryBuilder();
		$qb->select('id', 'slug')
		   ->from($this->getTableName());

		$result = $qb->execute();
		$map = [];
		while ($row = $result->fetch()) {
			$map[$row['slug']] = $row['id'];
		}
		$result->closeCursor();

		return $map;
	}
}
