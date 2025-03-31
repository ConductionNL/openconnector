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

use DateTime;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class EventMessageMapper
 *
 * Handles database operations for event messages
 */
class EventMessageMapper extends QBMapper
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
        parent::__construct($db, 'openconnector_event_messages');

    }//end __construct()


    /**
     * Find a message by ID
     *
     * @param         int $id The message ID
     * @psalm-param   int $id
     * @phpstan-param int $id
     * @return        EventMessage
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the message doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple messages match
     */
    public function find(int $id): EventMessage
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_event_messages')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);

    }//end find()


    /**
     * Find all messages matching the given criteria
     *
     * @param          int|null   $limit   Maximum number of results
     * @param          int|null   $offset  Number of records to skip
     * @param          array|null $filters Key-value pairs for filtering
     * @psalm-param    int|null $limit
     * @psalm-param    int|null $offset
     * @psalm-param    array<string, mixed>|null $filters
     * @phpstan-param  int|null $limit
     * @phpstan-param  int|null $offset
     * @phpstan-param  array<string, mixed>|null $filters
     * @return         EventMessage[]
     * @psalm-return   array<int, EventMessage>
     * @phpstan-return array<int, EventMessage>
     */
    public function findAll(?int $limit=null, ?int $offset=null, ?array $filters=[]): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_event_messages')
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

        return $this->findEntities($qb);

    }//end findAll()


    /**
     * Find messages that need to be retried
     *
     * @param          int $maxRetries Maximum number of retry attempts
     * @psalm-param    int $maxRetries
     * @phpstan-param  int $maxRetries
     * @return         EventMessage[]
     * @psalm-return   array<int, EventMessage>
     * @phpstan-return array<int, EventMessage>
     */
    public function findPendingRetries(int $maxRetries=5): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_event_messages')
            ->where(
                $qb->expr()->eq('status', $qb->createNamedParameter('pending')),
                $qb->expr()->lt('retry_count', $qb->createNamedParameter($maxRetries, IQueryBuilder::PARAM_INT)),
                $qb->expr()->orX(
                    $qb->expr()->isNull('next_attempt'),
                    $qb->expr()->lte('next_attempt', $qb->createNamedParameter(new DateTime(), IQueryBuilder::PARAM_DATE))
                )
            );

        return $this->findEntities($qb);

    }//end findPendingRetries()


    /**
     * Create a new message from array data
     *
     * @param         array $data Message data
     * @psalm-param   array<string, mixed> $data
     * @phpstan-param array<string, mixed> $data
     * @return        EventMessage
     */
    public function createFromArray(array $data): EventMessage
    {
        $obj = new EventMessage();
        $obj->hydrate($data);

        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set timestamps.
        $obj->setCreated(new DateTime());
        $obj->setUpdated(new DateTime());

        return $this->insert(entity: $obj);

    }//end createFromArray()


    /**
     * Update an existing message
     *
     * @param         int   $id   Message ID
     * @param         array $data Updated message data
     * @psalm-param   int $id
     * @psalm-param   array<string, mixed> $data
     * @phpstan-param int $id
     * @phpstan-param array<string, mixed> $data
     * @return        EventMessage
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the message doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple messages match
     */
    public function updateFromArray(int $id, array $data): EventMessage
    {
        $obj = $this->find($id);
        $obj->hydrate($data);

        // Update timestamp.
        $obj->setUpdated(new DateTime());

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Mark a message as delivered
     *
     * @param         int   $id       Message ID
     * @param         array $response Response from the consumer
     * @psalm-param   int $id
     * @psalm-param   array<string, mixed> $response
     * @phpstan-param int $id
     * @phpstan-param array<string, mixed> $response
     * @return        EventMessage
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the message doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple messages match
     */
    public function markDelivered(int $id, array $response): EventMessage
    {
        return $this->updateFromArray(
            $id,
            [
                'status'       => 'delivered',
                'lastResponse' => $response,
                'lastAttempt'  => new DateTime(),
            ]
        );

    }//end markDelivered()


    /**
     * Mark a message as failed
     *
     * @param         int   $id             Message ID
     * @param         array $response       Error response
     * @param         int   $backoffMinutes Minutes to wait before next attempt
     * @psalm-param   int $id
     * @psalm-param   array<string, mixed> $response
     * @psalm-param   int $backoffMinutes
     * @phpstan-param int $id
     * @phpstan-param array<string, mixed> $response
     * @phpstan-param int $backoffMinutes
     * @return        EventMessage
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the message doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple messages match
     */
    public function markFailed(int $id, array $response, int $backoffMinutes=5): EventMessage
    {
        $message = $this->find($id);
        $message->incrementRetry($backoffMinutes);

        return $this->updateFromArray(
            $id,
            [
                'status'       => 'failed',
                'lastResponse' => $response,
                'retryCount'   => $message->getRetryCount(),
                'lastAttempt'  => $message->getLastAttempt(),
                'nextAttempt'  => $message->getNextAttempt(),
            ]
        );

    }//end markFailed()


}//end class
