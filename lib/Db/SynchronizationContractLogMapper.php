<?php
/**
 * OpenConnector SynchronizationContractLog Mapper
 *
 * This file contains the mapper class for synchronization contract log data in the OpenConnector
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
use OCA\OpenConnector\Db\SynchronizationContractLog;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ISession;
use OCP\IUserSession;
use Symfony\Component\Uid\Uuid;
use OCP\Session\Exceptions\SessionNotAvailableException;

/**
 * Class SynchronizationContractLogMapper
 *
 * Mapper class for handling SynchronizationContractLog entities.
 *
 * @package OCA\OpenConnector\Db
 */
class SynchronizationContractLogMapper extends QBMapper
{


    /**
     * SynchronizationContractLogMapper constructor.
     *
     * @param IDBConnection $db          The database connection
     * @param IUserSession  $userSession The user session
     * @param ISession      $session     The session
     *
     * @return void
     */
    public function __construct(
        IDBConnection $db,
        private readonly IUserSession $userSession,
        private readonly ISession $session
    ) {
        parent::__construct($db, 'openconnector_synchronization_contract_logs');

    }//end __construct()


    /**
     * Find a SynchronizationContractLog by its ID.
     *
     * @param integer $id The ID of the synchronization contract log
     *
     * @return SynchronizationContractLog The found synchronization contract log
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the log entry is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one log entry is found
     */
    public function find(int $id): SynchronizationContractLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_synchronization_contract_logs')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity(query: $qb);

    }//end find()


    /**
     * Find a SynchronizationContractLog by synchronization ID.
     *
     * @param string $synchronizationId The synchronization ID to search for
     *
     * @return SynchronizationContractLog|null The found synchronization contract log or null if not found
     */
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

    }//end findOnSynchronizationId()


    /**
     * Find all SynchronizationContractLogs with optional filtering and pagination.
     *
     * @param integer|null $limit            Maximum number of results to return
     * @param integer|null $offset           Number of results to skip
     * @param array|null   $filters          Associative array of filters
     * @param array|null   $searchConditions Array of search conditions
     * @param array|null   $searchParams     Array of search parameters
     *
     * @return array An array of SynchronizationContractLog entities
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
            ->from('openconnector_synchronization_contract_logs')
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

        return $this->findEntities(query: $qb);

    }//end findAll()


    /**
     * Create a new SynchronizationContractLog from an array of data.
     *
     * @param array $object An array of SynchronizationContractLog data
     *
     * @return SynchronizationContractLog The newly created SynchronizationContractLog entity
     */
    public function createFromArray(array $object): SynchronizationContractLog
    {
        $obj = new SynchronizationContractLog();
        $obj->hydrate($object);

        // Set uuid if not provided.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Auto-fill userId from current user session.
        if ($obj->getUserId() === null && $this->userSession->getUser() !== null) {
            $obj->setUserId($this->userSession->getUser()->getUID());
        }

        // Auto-fill sessionId from current session.
        if ($obj->getSessionId() === null) {
            // Try catch because we could run this from a Job and in that case have no session.
            try {
                $obj->setSessionId($this->session->getId());
            } catch (SessionNotAvailableException $exception) {
                $obj->setSessionId(null);
            }
        }

        // If no synchronizationLogId is provided, we assume that the contract is run directly
        // from the synchronization log and set the synchronizationLogId to n.a.
        if ($obj->getSynchronizationLogId() === null) {
            $obj->setSynchronizationLogId('n.a.');
        }

        return $this->insert($obj);

    }//end createFromArray()


    /**
     * Update an existing SynchronizationContractLog from an array of data.
     *
     * @param integer $id     The ID of the SynchronizationContractLog to update
     * @param array   $object An array of updated SynchronizationContractLog data
     *
     * @return SynchronizationContractLog The updated SynchronizationContractLog entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the log entry is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one log entry is found
     */
    public function updateFromArray(int $id, array $object): SynchronizationContractLog
    {
        $obj = $this->find($id);
        $obj->hydrate($object);

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Get synchronization execution counts by date for a specific date range.
     *
     * @param DateTime $from Start date
     * @param DateTime $to   End date
     *
     * @return array Array of daily execution counts
     * @throws Exception If a database error occurs
     */
    public function getSyncStatsByDateRange(DateTime $from, DateTime $to): array
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
            $stats[$dateStr] = 0;
        }

        // Fill in actual values where they exist.
        while (($row = $result->fetch()) !== false) {
            $stats[$row['date']] = (int) $row['executions'];
        }

        return $stats;

    }//end getSyncStatsByDateRange()


    /**
     * Get synchronization execution counts by hour for a specific date range.
     *
     * @param DateTime $from Start date
     * @param DateTime $to   End date
     *
     * @return array Array of hourly execution counts
     * @throws Exception If a database error occurs
     */
    public function getSyncStatsByHourRange(DateTime $from, DateTime $to): array
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
        $stats  = [];

        // Fill in values from the query results.
        while (($row = $result->fetch()) !== false) {
            $stats[$row['hour']] = (int) $row['executions'];
        }

        return $stats;

    }//end getSyncStatsByHourRange()


}//end class
