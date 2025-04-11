<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\Source;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class SourceMapper
 *
 * This class is responsible for mapping Source entities to the database.
 * It provides methods for finding, creating, and updating Source objects.
 *
 * @package OCA\OpenConnector\Db
 */
class SourceMapper extends QBMapper
{
	/**
	 * The name of the database table for sources
	 */
	private const TABLE_NAME = 'openconnector_sources';

	public function __construct(IDBConnection $db)
	{
		parent::__construct($db, self::TABLE_NAME);
	}

	public function find(int $id): Source
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE_NAME)
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity(query: $qb);
	}

	public function findByRef(string $reference): array
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE_NAME)
			->where(
				$qb->expr()->eq('reference', $qb->createNamedParameter($reference))
			);

		return $this->findEntities(query: $qb);
	}

	public function findAll(?int $limit = null, ?int $offset = null, ?array $filters = [], ?array $searchConditions = [], ?array $searchParams = []): array
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from(self::TABLE_NAME)
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

		return $this->findEntities(query: $qb);
	}

	public function createFromArray(array $object): Source
	{
		$obj = new Source();
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
	}

	public function updateFromArray(int $id, array $object): Source
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
	 * Get the total count of all sources.
	 *
	 * @return int The total number of sources in the database.
	 */
	public function getTotalCallCount(): int
	{
		$qb = $this->db->getQueryBuilder();

		// Select count of all sources
		$qb->select($qb->createFunction('COUNT(*) as count'))
		   ->from(self::TABLE_NAME);

		$result = $qb->execute();
		$row = $result->fetch();

		// Return the total count
		return (int)$row['count'];
	}
}
