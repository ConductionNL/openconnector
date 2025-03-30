<?php
/**
 * OpenConnector EventSubscription Mapper
 *
 * This file contains the mapper class for event subscription data in the OpenConnector
 * application.
 *
 * @category  Mapper
 * @package   OpenConnector
 * @author    NextCloud Development Team <dev@nextcloud.com>
 * @copyright 2023 NextCloud GmbH
 * @license   AGPL-3.0 https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version   GIT: <git-id>
 * @link      https://nextcloud.com
 */

namespace OCA\OpenConnector\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class EventSubscriptionMapper
 *
 * Handles database operations for event subscriptions.
 *
 * @package OCA\OpenConnector\Db
 */
class EventSubscriptionMapper extends QBMapper
{


    /**
     * Constructor
     *
     * @param IDBConnection $db Database connection
     *
     * @return void
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_event_subscriptions');

    }//end __construct()


    /**
     * Find a subscription by ID.
     *
     * @param integer $id The subscription ID
     *
     * @return EventSubscription The found event subscription
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the subscription is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one subscription is found
     */
    public function find(int $id): EventSubscription
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_event_subscriptions')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);

    }//end find()


    /**
     * Find a subscription by reference.
     *
     * @param string $reference The subscription reference
     *
     * @return array An array of event subscriptions matching the reference
     */
    public function findByRef(string $reference): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_event_subscriptions')
            ->where(
                $qb->expr()->eq('reference', $qb->createNamedParameter($reference))
            );

        return $this->findEntities(query: $qb);

    }//end findByRef()


    /**
     * Find all subscriptions matching the given criteria.
     *
     * @param integer|null $limit   Maximum number of results
     * @param integer|null $offset  Number of records to skip
     * @param array|null   $filters Key-value pairs for filtering
     *
     * @return array An array of EventSubscription objects
     */
    public function findAll(
        ?int $limit=null,
        ?int $offset=null,
        ?array $filters=[]
    ): array {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_event_subscriptions')
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
     * Create a new subscription from array data.
     *
     * @param array $data Subscription data
     *
     * @return EventSubscription The newly created event subscription
     */
    public function createFromArray(array $data): EventSubscription
    {
        $obj = new EventSubscription();
        $obj->hydrate($data);

        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        return $this->insert(entity: $obj);

    }//end createFromArray()


    /**
     * Update an existing subscription.
     *
     * @param integer $id   Subscription ID
     * @param array   $data Updated subscription data
     *
     * @return EventSubscription The updated event subscription
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the subscription is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one subscription is found
     */
    public function updateFromArray(int $id, array $data): EventSubscription
    {
        $obj = $this->find($id);
        $obj->hydrate($data);

        return $this->update($obj);

    }//end updateFromArray()


}//end class
