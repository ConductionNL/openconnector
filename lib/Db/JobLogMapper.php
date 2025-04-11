<?php

namespace OCA\OpenConnector\Db;

use DateInterval;
use DatePeriod;
use DateTime;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class JobLogMapper
 *
 * This class is responsible for mapping JobLog entities to the database.
 * It provides methods for finding, creating, and updating JobLog objects.
 *
 * @package OCA\OpenConnector\Db
 */
class JobLogMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for job logs
     */
    private const TABLE_NAME = 'openconnector_job_logs';


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
     * Create a new JobLog entity instance
     *
     * @return JobLog A new JobLog instance
     */
    protected function createEntity(): Entity
    {
        return new JobLog();

    }//end createEntity()


    public function createForJob(Job $job, array $object): JobLog
    {
        $obj = new JobLog();
        $obj->hydrate($object);
        // Set uuid
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set job_id
        $obj->setJobId($job->getId());
        return $this->insert($obj);

    }//end createForJob()


    /**
     * Get the last call log.
     *
     * @return JobLog|null The last call log or null if no logs exist.
     * @throws MultipleObjectsReturnedException
     * @throws Exception
     */
    public function getLastCallLog(): ?JobLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->orderBy('created', 'DESC')
            ->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }

    }//end getLastCallLog()


    /**
     * Get job statistics grouped by date for a specific date range
     *
     * @param DateTime $from Start date
     * @param DateTime $to   End date
     *
     * @return array Array of daily statistics with counts per log level
     * @throws Exception
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
            ->from($this->getTableName())
            ->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
            ->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $result = $qb->execute();
        $stats  = [];

        // Create DatePeriod to iterate through all dates
        $period = new DatePeriod(
            $from,
            new DateInterval('P1D'),
            $to->modify('+1 day')
        );

        // Initialize all dates with zero values
        foreach ($period as $date) {
            $dateStr         = $date->format('Y-m-d');
            $stats[$dateStr] = [
                'info'    => 0,
                'warning' => 0,
                'error'   => 0,
                'debug'   => 0,
            ];
        }

        // Fill in actual values where they exist
        while ($row = $result->fetch()) {
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
     * Get job statistics grouped by hour for a specific date range
     *
     * @param DateTime $from Start date
     * @param DateTime $to   End date
     *
     * @return array Array of hourly statistics with counts per log level
     * @throws Exception
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
            ->from($this->getTableName())
            ->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
            ->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');

        $result = $qb->execute();
        $stats  = [];

        while ($row = $result->fetch()) {
            $stats[$row['hour']] = [
                'info'    => (int) $row['info'],
                'warning' => (int) $row['warning'],
                'error'   => (int) $row['error'],
                'debug'   => (int) $row['debug'],
            ];
        }

        return $stats;

    }//end getJobStatsByHourRange()


    /**
     * Find all job logs with optional filtering and pagination
     *
     * @param int|null $limit Maximum number of results to return
     * @param int|null $offset Number of results to skip
     * @param array|null $filters Associative array of filter conditions (column => value)
     * @param array|null $searchConditions Search conditions for the query
     * @param array|null $searchParams Parameters for the search conditions
     * @param array|null $ids List of IDs or UUIDs to search for
     * @return array<JobLog> Array of matching job log entities
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

}//end class
