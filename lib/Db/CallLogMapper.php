<?php
/**
 * OpenConnector CallLog Mapper
 *
 * This file contains the mapper class for call log data in the OpenConnector
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
 * Class CallLogMapper
 *
 * This class is responsible for mapping CallLog entities to the database.
 * It provides methods for finding, creating, and updating CallLog objects
 * as well as various statistical analysis functions.
 *
 * @package OCA\OpenConnector\Db
 */
class CallLogMapper extends QBMapper
{


    /**
     * CallLogMapper constructor.
     *
     * @param IDBConnection $db The database connection
     *
     * @return void
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_call_logs');

    }//end __construct()


    /**
     * Find a CallLog by its ID.
     *
     * @param int $id The ID of the CallLog
     *
     * @return CallLog The found CallLog entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the call log is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one call log is found
     */
    public function find(int $id): CallLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_call_logs')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);

    }//end find()


    /**
     * Find all CallLogs with optional filtering and pagination.
     *
     * @param int|null   $limit            Maximum number of results to return
     * @param int|null   $offset           Number of results to skip
     * @param array|null $filters          Associative array of filters
     * @param array|null $searchConditions Array of search conditions
     * @param array|null $searchParams     Array of search parameters
     *
     * @return array An array of CallLog entities
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
            ->from('openconnector_call_logs')
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
     * Create a new CallLog from an array of data.
     *
     * @param array $object An array of CallLog data
     *
     * @return CallLog The newly created CallLog entity
     */
    public function createFromArray(array $object): CallLog
    {
        $obj = new CallLog();
        $obj->hydrate($object);
        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        return $this->insert($obj);

    }//end createFromArray()


    /**
     * Update an existing CallLog from an array of data.
     *
     * @param int   $id     The ID of the CallLog to update
     * @param array $object An array of updated CallLog data
     *
     * @return CallLog The updated CallLog entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the call log is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one call log is found
     */
    public function updateFromArray(int $id, array $object): CallLog
    {
        $obj = $this->find($id);
        $obj->hydrate($object);

        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Clear expired logs from the database.
     *
     * This method deletes all call logs that have expired (i.e., their 'expired' date is earlier than the current date and time).
     *
     * @return boolean True if any logs were deleted, false otherwise
     * @throws Exception If a database error occurs
     */
    public function clearLogs(): bool
    {
        // Get the query builder.
        $qb = $this->db->getQueryBuilder();

        // Build the delete query.
        $qb->delete('openconnector_call_logs')
            ->where($qb->expr()->lt('expires', $qb->createFunction('NOW()')));

        // Execute the query and get the number of affected rows.
        $result = $qb->execute();

        // Return true if any rows were affected (i.e., any logs were deleted).
        return $result > 0;

    }//end clearLogs()


    /**
     * Get call log counts grouped by creation date.
     *
     * @return array An associative array where the key is the creation date and the value is the count of calls created on that date
     * @throws Exception If a database error occurs
     */
    public function getCallCountsByDate(): array
    {
        $qb = $this->db->getQueryBuilder();

        // Select the date part of the created timestamp and count of logs.
        $qb->select(
            $qb->createFunction('DATE(created) as date'),
            $qb->createFunction('COUNT(*) as count')
        )
            ->from('openconnector_call_logs')
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $result = $qb->execute();
        $counts = [];

        // Fetch results and build the return array.
        while (($row = $result->fetch()) !== false) {
            $counts[$row['date']] = (int) $row['count'];
        }

        return $counts;

    }//end getCallCountsByDate()


    /**
     * Get call log counts grouped by creation time (hour).
     *
     * @return array An associative array where the key is the creation time (hour) and the value is the count of calls created at that time
     * @throws Exception If a database error occurs
     */
    public function getCallCountsByTime(): array
    {
        $qb = $this->db->getQueryBuilder();

        // Select the hour part of the created timestamp and count of logs.
        $qb->select(
            $qb->createFunction('HOUR(created) as hour'),
            $qb->createFunction('COUNT(*) as count')
        )
            ->from('openconnector_call_logs')
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');

        $result = $qb->execute();
        $counts = [];

        // Fetch results and build the return array.
        while (($row = $result->fetch()) !== false) {
            $counts[$row['hour']] = (int) $row['count'];
        }

        return $counts;

    }//end getCallCountsByTime()


    /**
     * Get the total count of all call logs.
     *
     * @return int The total number of call logs in the database
     * @throws Exception If a database error occurs
     */
    public function getTotalCallCount(): int
    {
        $qb = $this->db->getQueryBuilder();

        // Select count of all logs.
        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_call_logs');

        $result = $qb->execute();
        $row    = $result->fetch();

        // Return the total count.
        return (int) $row['count'];

    }//end getTotalCallCount()


    /**
     * Get the last call log.
     *
     * @return CallLog|null The last call log or null if no logs exist
     * @throws Exception If a database error occurs
     * @throws MultipleObjectsReturnedException If more than one call log matches the query
     */
    public function getLastCallLog(): ?CallLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_call_logs')
            ->orderBy('created', 'DESC')
            ->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }

    }//end getLastCallLog()


    /**
     * Get call statistics grouped by date for a specific date range.
     *
     * @param DateTime $from Start date
     * @param DateTime $to   End date
     *
     * @return array Array of daily statistics with success and error counts
     * @throws Exception If a database error occurs
     */
    public function getCallStatsByDateRange(DateTime $from, DateTime $to): array
    {
        $qb = $this->db->getQueryBuilder();

        // Get the actual data from database.
        $qb->select(
            $qb->createFunction('DATE(created) as date'),
            $qb->createFunction(
                'SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as success'
            ),
            $qb->createFunction(
                'SUM(CASE WHEN status_code < 200 OR status_code >= 300 THEN 1 ELSE 0 END) as error'
            )
        )
            ->from('openconnector_call_logs')
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
                'success' => 0,
                'error'   => 0,
            ];
        }

        // Fill in actual values where they exist.
        while (($row = $result->fetch()) !== false) {
            $stats[$row['date']] = [
                'success' => (int) $row['success'],
                'error'   => (int) $row['error'],
            ];
        }

        return $stats;

    }//end getCallStatsByDateRange()


    /**
     * Get call statistics grouped by hour for a specific date range.
     *
     * @param DateTime $from Start date
     * @param DateTime $to   End date
     *
     * @return array Array of hourly statistics with success and error counts
     * @throws Exception If a database error occurs
     */
    public function getCallStatsByHourRange(DateTime $from, DateTime $to): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
            $qb->createFunction('HOUR(created) as hour'),
            $qb->createFunction(
                'SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as success'
            ),
            $qb->createFunction(
                'SUM(CASE WHEN status_code < 200 OR status_code >= 300 THEN 1 ELSE 0 END) as error'
            )
        )
            ->from('openconnector_call_logs')
            ->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
            ->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');

        $result = $qb->execute();
        $stats  = [];

        // Fill in values from the query results.
        while (($row = $result->fetch()) !== false) {
            $stats[$row['hour']] = [
                'success' => (int) $row['success'],
                'error'   => (int) $row['error'],
            ];
        }

        return $stats;

    }//end getCallStatsByHourRange()


}//end class
