<?php

namespace OCA\OpenConnector\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

class JobLogMapper extends QBMapper
{
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_job_logs');
    }

    public function find(int $id): JobLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_job_logs')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findAll(?int $limit = null, ?int $offset = null, ?array $filters = [], ?array $searchConditions = [], ?array $searchParams = []): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_job_logs')
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

	public function createForJob(Job $job, array $object): JobLog
	{
		$jobObject = [
			'jobId'         => $job->getId(),
			'jobClass'      => $job->getJobClass(),
			'jobListId'     => $job->getJobListId(),
			'arguments'     => $job->getArguments(),
			'lastRun'       => $job->getLastRun(),
			'nextRun'       => $job->getNextRun(),
		];

		$object = array_merge($jobObject, $object);

		return $this->createFromArray($object);
	}

    public function createFromArray(array $object): JobLog
    {
		if (isset($object['executionTime']) === false) {
			$object['executionTime'] = 0;
		}

        $obj = new JobLog();
		$obj->hydrate($object);
		// Set uuid
		if ($obj->getUuid() === null){
			$obj->setUuid(Uuid::v4());
		}
        return $this->insert($obj);
    }

    public function updateFromArray(int $id, array $object): JobLog
    {
        $obj = $this->find($id);
		$obj->hydrate($object);
		if ($obj->getUuid() === null){
			$obj->setUuid(Uuid::v4());
		}

        return $this->update($obj);
    }

    /**
     * Get the last call log.
     *
     * @return CallLog|null The last call log or null if no logs exist.
     */
    public function getLastCallLog(): ?JobLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('openconnector_job_logs')
           ->orderBy('created', 'DESC')
           ->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }
    }

    /**
     * Get job statistics grouped by date for a specific date range
     * 
     * @param \DateTime $from Start date
     * @param \DateTime $to End date
     * @return array Array of daily statistics with counts per log level
     */
    public function getJobStatsByDateRange(\DateTime $from, \DateTime $to): array 
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
                $qb->createFunction('DATE(created) as date'),
                $qb->createFunction('SUM(CASE WHEN level = \'INFO\' THEN 1 ELSE 0 END) as info'),
                $qb->createFunction('SUM(CASE WHEN level = \'WARNING\' THEN 1 ELSE 0 END) as warning'),
                $qb->createFunction('SUM(CASE WHEN level = \'ERROR\' THEN 1 ELSE 0 END) as error'),
                $qb->createFunction('SUM(CASE WHEN level = \'DEBUG\' THEN 1 ELSE 0 END) as debug')
            )
            ->from('openconnector_job_logs')
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
            $stats[$dateStr] = [
                'info' => 0,
                'warning' => 0,
                'error' => 0,
                'debug' => 0
            ];
        }

        // Fill in actual values where they exist
        while ($row = $result->fetch()) {
            $stats[$row['date']] = [
                'info' => (int)$row['info'],
                'warning' => (int)$row['warning'],
                'error' => (int)$row['error'],
                'debug' => (int)$row['debug']
            ];
        }

        return $stats;
    }

    /**
     * Get job statistics grouped by hour for a specific date range
     * 
     * @param \DateTime $from Start date
     * @param \DateTime $to End date
     * @return array Array of hourly statistics with counts per log level
     */
    public function getJobStatsByHourRange(\DateTime $from, \DateTime $to): array 
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
                $qb->createFunction('HOUR(created) as hour'),
                $qb->createFunction('SUM(CASE WHEN level = \'INFO\' THEN 1 ELSE 0 END) as info'),
                $qb->createFunction('SUM(CASE WHEN level = \'WARNING\' THEN 1 ELSE 0 END) as warning'),
                $qb->createFunction('SUM(CASE WHEN level = \'ERROR\' THEN 1 ELSE 0 END) as error'),
                $qb->createFunction('SUM(CASE WHEN level = \'DEBUG\' THEN 1 ELSE 0 END) as debug')
            )
            ->from('openconnector_job_logs')
            ->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
            ->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');

        $result = $qb->execute();
        $stats = [];

        while ($row = $result->fetch()) {
            $stats[$row['hour']] = [
                'info' => (int)$row['info'],
                'warning' => (int)$row['warning'],
                'error' => (int)$row['error'],
                'debug' => (int)$row['debug']
            ];
        }

        return $stats;
    }
}
