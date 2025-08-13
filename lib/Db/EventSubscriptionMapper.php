<?php

namespace OCA\OpenConnector\Db;

use OC\Server;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
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
     * Get the logger using lazy resolution
     *
     * @return LoggerInterface The logger instance
     */
    private function getLogger(): LoggerInterface
    {
        return Server::get(LoggerInterface::class);
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
        $obj = new EventSubscription();
        $obj->hydrate($data);
        
        // Set uuid
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        return $this->insert(entity: $obj);
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
        $obj = $this->find($id);
        $obj->hydrate($data);

        return $this->update($obj);
    }

    /**
     * Apply filters to a query builder.
     *
     * @param \OCP\DB\QueryBuilder\IQueryBuilder $qb The query builder to apply filters to
     * @param array $filters The filters to apply
     * @return void
     */
    private function applyFilters(\OCP\DB\QueryBuilder\IQueryBuilder $qb, array $filters): void
    {
        foreach ($filters as $filter => $value) {
            if ($value === 'IS NOT NULL') {
                $qb->andWhere($qb->expr()->isNotNull($filter));
            } elseif ($value === 'IS NULL') {
                $qb->andWhere($qb->expr()->isNull($filter));
            } elseif (is_array($value)) {
                // Handle array values like ['IS NULL', ''] or ['<', 'NOW()']
                $conditions = [];
                foreach ($value as $val) {
                    if ($val === 'IS NULL') {
                        $conditions[] = $qb->expr()->isNull($filter);
                    } elseif ($val === 'IS NOT NULL') {
                        $conditions[] = $qb->expr()->isNotNull($filter);
                    } elseif ($val === 'NOW()') {
                        $conditions[] = $qb->expr()->lt($filter, $qb->createFunction('NOW()'));
                    } else {
                        $conditions[] = $qb->expr()->eq($filter, $qb->createNamedParameter($val));
                    }
                }
                if (!empty($conditions)) {
                    $qb->andWhere($qb->expr()->orX(...$conditions));
                }
            } else {
                $qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
            }
        }
    }

    /**
     * Count event subscriptions with optional filters.
     *
     * @param array $filters Optional filters to apply to the count query
     * @return int The number of event subscriptions matching the filters
     * @throws \Exception If the count query fails
     */
    public function count(array $filters = []): int
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->createFunction('COUNT(*)'))
               ->from($this->getTableName());

            // Apply filters using the helper method
            $this->applyFilters($qb, $filters);

            $result = $qb->executeQuery();
            return (int) $result->fetchOne();
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to count event subscriptions: ' . $e->getMessage(), [
                'app' => 'openconnector',
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Calculate total size of event subscriptions with optional filters.
     *
     * @param array $filters Optional filters to apply to the size calculation
     * @return int The total size in bytes of event subscriptions matching the filters
     * @throws \Exception If the size calculation fails
     */
    public function size(array $filters = []): int
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->createFunction('COALESCE(SUM(size), 0)'))
               ->from($this->getTableName());

            // Apply filters using the helper method
            $this->applyFilters($qb, $filters);

            $result = $qb->executeQuery();
            return (int) $result->fetchOne();
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to calculate event subscriptions size: ' . $e->getMessage(), [
                'app' => 'openconnector',
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Clear all event subscriptions (delete all records).
     *
     * @return bool True if the operation was successful
     * @throws \Exception If the clear operation fails
     */
    public function clearEventSubscriptions(): bool
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->delete($this->getTableName());
            $qb->executeStatement();
            return true;
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to clear event subscriptions: ' . $e->getMessage(), [
                'app' => 'openconnector',
                'exception' => $e
            ]);
            throw $e;
        }
    }
}
