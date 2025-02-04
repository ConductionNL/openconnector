<?php

namespace OCA\OpenConnector\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class EventSubscriptionMapper
 *
 * Handles database operations for event subscriptions
 *
 * @package OCA\OpenConnector\Db
 */
class EventSubscriptionMapper extends QBMapper
{
    /**
     * Constructor
     *
     * @param IDBConnection $db Database connection
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_event_subscriptions');
    }

    /**
     * Find a subscription by ID
     *
     * @param int $id The subscription ID
     * @return EventSubscription
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
    }

	/**
	 * Find a subscription by reference
	 *
	 * @param int $id The subscription ID
	 * @return EventSubscription
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
	}

    /**
     * Find all subscriptions matching the given criteria
     *
     * @param int|null $limit Maximum number of results
     * @param int|null $offset Number of records to skip
     * @param array|null $filters Key-value pairs for filtering
     * @return EventSubscription[]
     */
    public function findAll(?int $limit = null, ?int $offset = null, ?array $filters = []): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_event_subscriptions')
            ->setMaxResults($limit)
            ->setFirstResult($offset);

        foreach ($filters as $filter => $value) {
            if ($value === 'IS NOT NULL') {
                $qb->andWhere($qb->expr()->isNotNull($filter));
            } elseif ($value === 'IS NULL') {
                $qb->andWhere($qb->expr()->isNull($filter));
            } else {
                $qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
            }
        }

        return $this->findEntities($qb);
    }

    /**
     * Create a new subscription from array data
     *
     * @param array $data Subscription data
     * @return EventSubscription
     */
    public function createFromArray(array $data): EventSubscription
    {
        $subscription = new EventSubscription();
        $subscription->setUuid(Uuid::v4()->toString());
        $subscription->hydrate($data);
        return $this->insert($subscription);
    }

    /**
     * Update an existing subscription
     *
     * @param int $id Subscription ID
     * @param array $data Updated subscription data
     * @return EventSubscription
     */
    public function updateFromArray(int $id, array $data): EventSubscription
    {
        $subscription = $this->find($id);
        $subscription->hydrate($data);
        return $this->update($subscription);
    }
}
