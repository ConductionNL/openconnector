<?php
/**
 * OpenConnector - Connect your Nextcloud to external services
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
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

use OCA\OpenConnector\Db\Event;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Handles database operations for events including CRUD operations
 */
class EventMapper extends QBMapper
{


    /**
     * Constructor
     *
     * @param         IDBConnection $db Database connection
     * @psalm-param   IDBConnection $db
     * @phpstan-param IDBConnection $db
     * @return        void
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_events');

    }//end __construct()


    /**
     * Find a single event by ID
     *
     * @param         int $id The event ID
     * @psalm-param   int $id
     * @phpstan-param int $id
     * @return        Event The found event
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the event doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple events match
     */
    public function find(int $id): Event
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_events')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity(query: $qb);

    }//end find()


    /**
     * Find all events with optional filtering and pagination
     *
     * @param          int|null   $limit            Maximum number of results
     * @param          int|null   $offset           Number of records to skip
     * @param          array|null $filters          Key-value pairs for filtering
     * @param          array|null $searchConditions Search conditions
     * @param          array|null $searchParams     Search parameters
     * @psalm-param    int|null $limit
     * @psalm-param    int|null $offset
     * @psalm-param    array<string, mixed>|null $filters
     * @psalm-param    array<int, string>|null $searchConditions
     * @psalm-param    array<string, mixed>|null $searchParams
     * @phpstan-param  int|null $limit
     * @phpstan-param  int|null $offset
     * @phpstan-param  array<string, mixed>|null $filters
     * @phpstan-param  array<int, string>|null $searchConditions
     * @phpstan-param  array<string, mixed>|null $searchParams
     * @return         Event[] Array of Event objects
     * @psalm-return   array<int, Event>
     * @phpstan-return array<int, Event>
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
            ->from('openconnector_events')
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
     * Create a new event from array data
     *
     * @param         array $object Array of event data
     * @psalm-param   array<string, mixed> $object
     * @phpstan-param array<string, mixed> $object
     * @return        Event The created event
     */
    public function createFromArray(array $object): Event
    {
        $obj = new Event();
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

    }//end createFromArray()


    /**
     * Update an existing event from array data
     *
     * @param         int   $id     Event ID to update
     * @param         array $object Array of event data
     * @psalm-param   int $id
     * @psalm-param   array<string, mixed> $object
     * @phpstan-param int $id
     * @phpstan-param array<string, mixed> $object
     * @return        Event The updated event
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the event doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple events match
     */
    public function updateFromArray(int $id, array $object): Event
    {
        $obj = $this->find($id);

        // Set version
        if (empty($obj->getVersion()) === true) {
            $object['version'] = '0.0.1';
        } else if (empty($object['version']) === true) {
            // Update version
            $version = explode('.', $obj->getVersion());
            if (isset($version[2]) === true) {
                $version[2]        = ((int) $version[2] + 1);
                $object['version'] = implode('.', $version);
            }
        }

        $obj->hydrate($object);

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Get the total count of all events
     *
     * @return int The total number of events in the database
     */
    public function getTotalCount(): int
    {
        $qb = $this->db->getQueryBuilder();

        // Select count of all events
        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_events');

        $result = $qb->execute();
        $row    = $result->fetch();

        // Return the total count
        return (int) $row['count'];

    }//end getTotalCount()


}//end class
