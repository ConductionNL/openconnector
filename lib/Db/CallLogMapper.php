<?php

namespace OCA\OpenConnector\Db;

use DateInterval;
use DatePeriod;
use DateTime;
use OCA\OpenConnector\Db\CallLog;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\BaseMapper;

/**
 * Class CallLogMapper
 *
 * This class is responsible for mapping CallLog entities to the database.
 * It provides methods for finding, creating, and updating CallLog objects.
 *
 * @package OCA\OpenConnector\Db
 * @extends BaseMapper<CallLog>
 */
class CallLogMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for call logs
     */
    private const TABLE_NAME = 'openconnector_call_logs';


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
     * Create a new CallLog entity instance
     *
     * @return CallLog A new CallLog instance
     */
    protected function createEntity(): Entity
    {
        return new CallLog();

    }//end createEntity()


    /**
     * Get call statistics grouped by date for a specific date range
     *
     * @param  DateTime $from Start date
     * @param  DateTime $to   End date
     * @return array Array of daily statistics with counts per log level
     */
    public function getCallStatsByDateRange(DateTime $from, DateTime $to): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
            $qb->createFunction('DATE(created) as date'),
            $qb->createFunction('SUM(CASE WHEN level = \'INFO\' THEN 1 ELSE 0 END) as info'),
            $qb->createFunction('SUM(CASE WHEN level = \'WARNING\' THEN 1 ELSE 0 END) as warning'),
            $qb->createFunction('SUM(CASE WHEN level = \'ERROR\' THEN 1 ELSE 0 END) as error'),
            $qb->createFunction('SUM(CASE WHEN level = \'DEBUG\' THEN 1 ELSE 0 END) as debug')
        )
            ->from(self::TABLE_NAME)
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

    }//end getCallStatsByDateRange()


    /**
     * Get call statistics grouped by hour for a specific date range
     *
     * @param  DateTime $from Start date
     * @param  DateTime $to   End date
     * @return array Array of hourly statistics with counts per log level
     */
    public function getCallStatsByHourRange(DateTime $from, DateTime $to): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
            $qb->createFunction('HOUR(created) as hour'),
            $qb->createFunction('SUM(CASE WHEN level = \'INFO\' THEN 1 ELSE 0 END) as info'),
            $qb->createFunction('SUM(CASE WHEN level = \'WARNING\' THEN 1 ELSE 0 END) as warning'),
            $qb->createFunction('SUM(CASE WHEN level = \'ERROR\' THEN 1 ELSE 0 END) as error'),
            $qb->createFunction('SUM(CASE WHEN level = \'DEBUG\' THEN 1 ELSE 0 END) as debug')
        )
            ->from(self::TABLE_NAME)
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

    }//end getCallStatsByHourRange()


    /**
     * Get the last call log
     *
     * @return CallLog|null The last call log or null if no logs exist
     */
    public function getLastCallLog(): ?CallLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from(self::TABLE_NAME)
            ->orderBy('created', 'DESC')
            ->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }

    }//end getLastCallLog()


    /**
     * Clear all call logs
     *
     * @return int Number of deleted entries
     */
    public function clearLogs(): int
    {
        $qb = $this->db->getQueryBuilder();

        $qb->delete(self::TABLE_NAME);

        return $qb->executeStatement();

    }//end clearLogs()


    /**
     * Get call log counts grouped by creation date.
     *
     * @return array An associative array where the key is the creation date and the value is the count of calls created on that date.
     * @throws Exception
     */
    public function getCallCountsByDate(): array
    {
        $qb = $this->db->getQueryBuilder();

        // Select the date part of the created timestamp and count of logs
        $qb->select($qb->createFunction('DATE(created) as date'), $qb->createFunction('COUNT(*) as count'))
            ->from(self::TABLE_NAME)
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $result = $qb->execute();
        $counts = [];

        // Fetch results and build the return array
        while ($row = $result->fetch()) {
            $counts[$row['date']] = (int) $row['count'];
        }

        return $counts;

    }//end getCallCountsByDate()


    /**
     * Get call log counts grouped by creation time (hour).
     *
     * @return array An associative array where the key is the creation time (hour) and the value is the count of calls created at that time.
     * @throws Exception
     */
    public function getCallCountsByTime(): array
    {
        $qb = $this->db->getQueryBuilder();

        // Select the hour part of the created timestamp and count of logs
        $qb->select($qb->createFunction('HOUR(created) as hour'), $qb->createFunction('COUNT(*) as count'))
            ->from(self::TABLE_NAME)
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');

        $result = $qb->execute();
        $counts = [];

        // Fetch results and build the return array
        while ($row = $result->fetch()) {
            $counts[$row['hour']] = (int) $row['count'];
        }

        return $counts;

    }//end getCallCountsByTime()


    /**
     * Find all call logs with optional filtering and pagination
     *
     * @param int|null $limit Maximum number of results to return
     * @param int|null $offset Number of results to skip
     * @param array|null $filters Associative array of filter conditions (column => value)
     * @param array|null $searchConditions Search conditions for the query
     * @param array|null $searchParams Parameters for the search conditions
     * @param array|null $ids List of IDs or UUIDs to search for
     * @return array<CallLog> Array of matching call log entities
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
