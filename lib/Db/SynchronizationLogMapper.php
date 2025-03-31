<?php
/**
 * OpenConnector SynchronizationLog Mapper
 *
 * This file contains the mapper class for synchronization log data in the OpenConnector
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

use DateTime;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\ISession;
use OCP\IUserSession;
use Symfony\Component\Uid\Uuid;
use OCP\Session\Exceptions\SessionNotAvailableException;

/**
 * Class SynchronizationLogMapper
 *
 * Mapper class for handling SynchronizationLog entities.
 *
 * @package OCA\OpenConnector\Db
 */
class SynchronizationLogMapper extends QBMapper
{


    /**
     * SynchronizationLogMapper constructor.
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
        parent::__construct($db, 'openconnector_synchronization_logs');

    }//end __construct()


    /**
     * Find a SynchronizationLog by its ID.
     *
     * @param integer $id The ID of the synchronization log
     *
     * @return SynchronizationLog The found synchronization log
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the log entry is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one log entry is found
     */
    public function find(int $id): SynchronizationLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_synchronization_logs')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);

    }//end find()


    /**
     * Find all SynchronizationLogs with optional filtering and pagination.
     *
     * @param integer|null $limit            Maximum number of results to return
     * @param integer|null $offset           Number of results to skip
     * @param array|null   $filters          Associative array of filters
     * @param array|null   $searchConditions Array of search conditions
     * @param array|null   $searchParams     Array of search parameters
     *
     * @return array An array of SynchronizationLog entities
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
            ->from('openconnector_synchronization_logs')
            ->orderBy('created', 'DESC')
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
     * Process contracts array to ensure it only contains valid UUIDs.
     *
     * @param array $contracts Array of contracts or contract objects
     *
     * @return array Processed array containing only valid UUIDs
     */
    private function processContracts(array $contracts): array
    {
        return array_values(
            array_filter(
                array_map(
                    function ($contract) {
                        if (is_object($contract) === true) {
                            // If it's an object with getUuid method, use that.
                            if (method_exists($contract, 'getUuid') === true) {
                                $uuid = $contract->getUuid();
                                if (empty($uuid) === false) {
                                    return $uuid;
                                }

                                return null;
                            }

                            return null;
                        }

                        // If it's already a string (UUID), return it.
                        if (is_string($contract) === true) {
                            return $contract;
                        }

                        return null;
                    },
                    $contracts
                )
            )
        );

    }//end processContracts()


    /**
     * Creates a new synchronization log entry.
     *
     * @param array $object The log data
     *
     * @return SynchronizationLog The created log entry
     */
    public function createFromArray(array $object): SynchronizationLog
    {
        $obj = new SynchronizationLog();

        // Auto-fill system fields.
        $object['uuid']   = ($object['uuid'] ?? Uuid::v4());
        $object['userId'] = ($object['userId'] ?? $this->userSession->getUser()?->getUID());

        // Catch error from session, because when running from a Job this might cause an error
        // preventing the Job from being ran.
        try {
            $object['sessionId'] = ($object['sessionId'] ?? $this->session->getId());
        } catch (SessionNotAvailableException $exception) {
            $object['sessionId'] = null;
        }

        $object['created'] = ($object['created'] ?? new DateTime());
        $object['expires'] = ($object['expires'] ?? new DateTime('+30 days'));
        $object['test']    = ($object['test'] ?? false);
        $object['force']   = ($object['force'] ?? false);

        // Process contracts in results if they exist.
        if (isset($object['result']['contracts']) === true) {
            if (is_array($object['result']['contracts']) === true) {
                $object['result']['contracts'] = $this->processContracts($object['result']['contracts']);
            }
        }

        $obj->hydrate($object);

        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        return $this->insert($obj);

    }//end createFromArray()


    /**
     * Updates an existing synchronization log entry.
     *
     * @param integer $id     The ID of the log entry to update
     * @param array   $object The updated log data
     *
     * @return SynchronizationLog The updated log entry
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the log entry is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one log entry is found
     */
    public function updateFromArray(int $id, array $object): SynchronizationLog
    {
        $obj = $this->find($id);

        // Process contracts in results if they exist.
        if ((isset($object['result']['contracts']) === true) && (is_array($object['result']['contracts']) === true)) {
            $object['result']['contracts'] = $this->processContracts($object['result']['contracts']);
        }

        $obj->hydrate($object);

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Get the total count of synchronization logs.
     *
     * @param array $filters Optional filters to apply
     *
     * @return integer The total number of logs
     */
    public function getTotalCount(array $filters=[]): int
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_synchronization_logs');

        foreach ($filters as $filter => $value) {
            if ($value === 'IS NOT NULL') {
                $qb->andWhere($qb->expr()->isNotNull($filter));
            } else if ($value === 'IS NULL') {
                $qb->andWhere($qb->expr()->isNull($filter));
            } else {
                $qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
            }
        }

        $result = $qb->executeQuery();
        $row    = $result->fetch();
        $result->closeCursor();

        return (int) $row['count'];

    }//end getTotalCount()


    /**
     * Cleans up expired log entries.
     *
     * @return integer Number of deleted entries
     */
    public function cleanupExpired(): int
    {
        $qb = $this->db->getQueryBuilder();

        $qb->delete('openconnector_synchronization_logs')
            ->where($qb->expr()->lt('expires', $qb->createNamedParameter(new DateTime(), IQueryBuilder::PARAM_DATE)));

        return $qb->executeStatement();

    }//end cleanupExpired()


}//end class
