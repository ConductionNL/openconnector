<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use OCP\AppFramework\Db\Entity;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class EventMessageMapper
 *
 * Handles database operations for event messages
 *
 * @package OCA\OpenConnector\Db
 * @extends BaseMapper<EventMessage>
 */
class EventMessageMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for event messages
     */
    private const TABLE_NAME = 'openconnector_event_messages';


    /**
     * Constructor
     *
     * @param IDBConnection $db Database connection
     */
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
     * Create a new EventMessage entity instance
     *
     * @return EventMessage A new EventMessage instance
     */
    protected function createEntity(): Entity
    {
        return new EventMessage();

    }//end createEntity()


    /**
     * Find messages that need to be retried
     *
     * @param  int $maxRetries Maximum number of retry attempts
     * @return EventMessage[]
     */
    public function findPendingRetries(int $maxRetries=5): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
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
     * Mark a message as delivered
     *
     * @param  int   $id       Message ID
     * @param  array $response Response from the consumer
     * @return EventMessage
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
     * @param  int   $id             Message ID
     * @param  array $response       Error response
     * @param  int   $backoffMinutes Minutes to wait before next attempt
     * @return EventMessage
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
