<?php
/**
 * OpenConnector JobLog Mapper
 *
 * This file contains the mapper class for job log data in the OpenConnector
 * application.
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

use DateInterval;
use DatePeriod;
use DateTime;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class JobLogMapper
 *
 * Mapper class for handling JobLog entities.
 *
 * @package OCA\OpenConnector\Db
 */
class JobLogMapper extends QBMapper
{


    /**
     * JobLogMapper constructor.
     *
     * @param IDBConnection $db The database connection
     *
     * @return void
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_job_logs');

    }//end __construct()


    /**
     * Find a JobLog by its ID.
     *
     * @param integer $id The ID of the job log
     *
     * @return JobLog The found job log
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the job log is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one job log is found
     */
    public function find(int $id): JobLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_job_logs')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);

    }//end find()


    /**
     * Find all JobLogs with optional filtering and pagination.
     *
     * @param integer|null $limit            Maximum number of results to return
     * @param integer|null $offset           Number of results to skip
     * @param array|null   $filters          Associative array of filters
     * @param array|null   $searchConditions Array of search conditions
     * @param array|null   $searchParams     Array of search parameters
     *
     * @return array An array of JobLog entities
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
            ->from('openconnector_job_logs')
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
     * Create a JobLog for a specific Job.
     *
     * @param Job   $job    The job to create a log for
     * @param array $object Additional log data
     *
     * @return JobLog The newly created JobLog entity
     */
    public function createForJob(Job $job, array $object): JobLog
    {
        $jobObject = [
            'jobId'     => $job->getId(),
            'jobClass'  => $job->getJobClass(),
            'jobListId' => $job->getJobListId(),
            'arguments' => $job->getArguments(),
            'lastRun'   => $job->getLastRun(),
            'nextRun'   => $job->getNextRun(),
        ];

        $object = array_merge($jobObject, $object);

        return $this->createFromArray($object);

    }//end createForJob()


    /**
     * Create a new JobLog from an array of data.
     *
     * @param array $object An array of JobLog data
     *
     * @return JobLog The newly created JobLog entity
     */
    public function createFromArray(array $object): JobLog
    {
        if (isset($object['executionTime']) === false) {
            $object['executionTime'] = 0;
        }

        $obj = new JobLog();
        $obj->hydrate($object);
        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        return $this->insert($obj);

    }//end createFromArray()


    /**
     * Update an existing JobLog from an array of data.
     *
     * @param integer $id     The ID of the JobLog to update
     * @param array   $object An array of updated JobLog data
     *
     * @return JobLog The updated JobLog entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the job log is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one job log is found
     */
    public function updateFromArray(int $id, array $object): JobLog
    {
        $obj = $this->find($id);
        $obj->hydrate($object);
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Get the last job log.
     *
     * @return JobLog|null The last job log or null if no logs exist
     * @throws MultipleObjectsReturnedException If more than one job log matches the query
     * @throws Exception If a database error occurs
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

    }//end getLastCallLog()


    /**
     * Get job statistics grouped by date for a specific date range.
     *
     * @param DateTime $from Start date
     * @param DateTime $to   End date
     *
     * @return array Array of daily statistics with counts per log level
     * @throws Exception If a database error occurs
     */
    public function getJobStatsByDateRange(DateTime $from, DateTime $to): array
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
        $stats  = [];

        // Create DatePeriod to iterate through all dates.
        $period = new DatePeriod(
            $from,
            new DateInterval('P1D'),
            $to->modify('+1 day')
        );

        // Initialize all dates with zero values.
        foreach ($period as $date) {
            $dateStr         = $date->format('Y-m-d');
            $stats[$dateStr] = [
                'info'    => 0,
                'warning' => 0,
                'error'   => 0,
                'debug'   => 0,
            ];
        }

        // Fill in actual values where they exist.
        while (($row = $result->fetch()) !== false) {
            $stats[$row['date']] = [
                'info'    => (int) $row['info'],
                'warning' => (int) $row['warning'],
                'error'   => (int) $row['error'],
                'debug'   => (int) $row['debug'],
            ];
        }

        return $stats;

    }//end getJobStatsByDateRange()


    /**
     * Get job statistics grouped by hour for a specific date range.
     *
     * @param DateTime $from Start date
     * @param DateTime $to   End date
     *
     * @return array Array of hourly statistics with counts per log level
     * @throws Exception If a database error occurs
     */
    public function getJobStatsByHourRange(DateTime $from, DateTime $to): array
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
        $stats  = [];

        // Fill in values from the query results.
        while (($row = $result->fetch()) !== false) {
            $stats[$row['hour']] = [
                'info'    => (int) $row['info'],
                'warning' => (int) $row['warning'],
                'error'   => (int) $row['error'],
                'debug'   => (int) $row['debug'],
            ];
        }

        return $stats;

    }//end getJobStatsByHourRange()


}//end class
