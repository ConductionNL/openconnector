<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\SynchronizationContractLog;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

class SynchronizationContractLogMapper extends QBMapper
{
	public function __construct(IDBConnection $db)
	{
		parent::__construct($db, 'openconnector_synchronization_contract_logs');
	}

	public function find(int $id): SynchronizationContractLog
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_synchronization_contract_logs')
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
			);

		return $this->findEntity(query: $qb);
	}

	public function findOnSynchronizationId(string $synchronizationId): ?SynchronizationContractLog
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_synchronization_contract_logs')
			->where(
				$qb->expr()->eq('synchronization_id', $qb->createNamedParameter($synchronizationId))
			);

		try {
			return $this->findEntity($qb);
		} catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
			return null;
		}
	}

	public function findAll(?int $limit = null, ?int $offset = null, ?array $filters = [], ?array $searchConditions = [], ?array $searchParams = []): array
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select('*')
			->from('openconnector_synchronization_contract_logs')
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

	public function createFromArray(array $object): SynchronizationContractLog
	{
		$obj = new SynchronizationContractLog();
		$obj->hydrate($object);
		// Set uuid
		if ($obj->getUuid() === null){
			$obj->setUuid(Uuid::v4());
		}
		return $this->insert($obj);
	}

	public function updateFromArray(int $id, array $object): SynchronizationContractLog
	{
		$obj = $this->find($id);
		$obj->hydrate($object);

		return $this->update($obj);
	}

	/**
	 * Get synchronization execution counts by date for a specific date range
	 * 
	 * @param \DateTime $from Start date
	 * @param \DateTime $to End date
	 * @return array Array of daily execution counts
	 */
	public function getSyncStatsByDateRange(\DateTime $from, \DateTime $to): array 
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select(
				$qb->createFunction('DATE(created) as date'),
				$qb->createFunction('COUNT(*) as executions')
			)
			->from('openconnector_synchronization_contract_logs')
			->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
			->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
			->groupBy('date')
			->orderBy('date', 'ASC');

		$result = $qb->execute();
		$stats = [];

		// Create DatePeriod to iterate through all dates
		$period = new \DatePeriod(
			$from,
			new \DateInterval('P1D'),
			$to->modify('+1 day')
		);

		// Initialize all dates with zero values
		foreach ($period as $date) {
			$dateStr = $date->format('Y-m-d');
			$stats[$dateStr] = 0;
		}

		// Fill in actual values where they exist
		while ($row = $result->fetch()) {
			$stats[$row['date']] = (int)$row['executions'];
		}

		return $stats;
	}

	/**
	 * Get synchronization execution counts by hour for a specific date range
	 * 
	 * @param \DateTime $from Start date
	 * @param \DateTime $to End date
	 * @return array Array of hourly execution counts
	 */
	public function getSyncStatsByHourRange(\DateTime $from, \DateTime $to): array 
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select(
				$qb->createFunction('HOUR(created) as hour'),
				$qb->createFunction('COUNT(*) as executions')
			)
			->from('openconnector_synchronization_contract_logs')
			->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
			->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
			->groupBy('hour')
			->orderBy('hour', 'ASC');

		$result = $qb->execute();
		$stats = [];

		while ($row = $result->fetch()) {
			$stats[$row['hour']] = (int)$row['executions'];
		}

		return $stats;
	}
}
