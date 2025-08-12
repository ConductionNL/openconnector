<?php

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

class CallLogMapper extends QBMapper
{
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_call_logs');
    }

    public function find(int $id): CallLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_call_logs')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findAll(?int $limit = null, ?int $offset = null, ?array $filters = [], ?array $searchConditions = [], ?array $searchParams = [], ?array $sortFields = []): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_call_logs')
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

        if (empty($searchConditions) === false) {
            $qb->andWhere('(' . implode(' OR ', $searchConditions) . ')');
            foreach ($searchParams as $param => $value) {
                $qb->setParameter($param, $value);
            }
        }

        foreach ($sortFields as $field => $direction) {
            $qb->addOrderBy($field, $direction);
        }

        return $this->findEntities($qb);
    }

    public function createFromArray(array $object): CallLog
    {
        $obj = new CallLog();
		$obj->hydrate($object);
		// Set uuid
		if ($obj->getUuid() === null) {
			$obj->setUuid(Uuid::v4());
		}

		// Calculate and set size if not provided
		if ($obj->getSize() === null) {
			$obj->setSize($this->calculateLogSize($obj));
		}

        return $this->insert($obj);
    }

    public function updateFromArray(int $id, array $object): CallLog
    {
        $obj = $this->find($id);
		$obj->hydrate($object);;
		// Set uuid
		if ($obj->getUuid() === null) {
			$obj->setUuid(Uuid::v4());
		}

        return $this->update($obj);
    }

	/**
	 * Clear expired logs from the database.
	 *
	 * This method deletes all call logs that have expired (i.e., their 'expires' date is earlier than the current date and time)
	 * and have the 'expires' column set. This helps maintain database performance by removing old log entries that are no longer needed.
	 *
	 * @return bool True if any logs were deleted, false otherwise.
	 *
	 * @throws \Exception Database operation exceptions
	 *
	 * @psalm-return bool
	 * @phpstan-return bool
	 */
    public function clearLogs(): bool
    {
        try {
            // Get the query builder for database operations
            $qb = $this->db->getQueryBuilder();

            // Build the delete query to remove expired call logs that have the 'expires' column set
            $qb->delete('openconnector_call_logs')
               ->where($qb->expr()->isNotNull('expires'))
               ->andWhere($qb->expr()->lt('expires', $qb->createFunction('NOW()')));

            // Execute the query and get the number of affected rows
            $result = $qb->executeStatement();

            // Return true if any rows were affected (i.e., any logs were deleted)
            return $result > 0;
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            \OC::$server->getLogger()->error('Failed to clear expired call logs: ' . $e->getMessage(), [
                'app' => 'openconnector',
                'exception' => $e
            ]);
            
            // Re-throw the exception so the caller knows something went wrong
            throw $e;
        }
    }

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
           ->from('openconnector_call_logs')
           ->groupBy('date')
           ->orderBy('date', 'ASC');

        $result = $qb->execute();
        $counts = [];

        // Fetch results and build the return array
        while ($row = $result->fetch()) {
            $counts[$row['date']] = (int)$row['count'];
        }

        return $counts;
    }

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
           ->from('openconnector_call_logs')
           ->groupBy('hour')
           ->orderBy('hour', 'ASC');

        $result = $qb->execute();
        $counts = [];

        // Fetch results and build the return array
        while ($row = $result->fetch()) {
            $counts[$row['hour']] = (int)$row['count'];
        }

        return $counts;
    }

	/**
	 * Get the total count of all call logs.
	 *
	 * @return int The total number of call logs in the database.
	 * @throws Exception
	 */
    public function getTotalCallCount(): int
    {
        $qb = $this->db->getQueryBuilder();

        // Select count of all logs
        $qb->select($qb->createFunction('COUNT(*) as count'))
           ->from('openconnector_call_logs');

        $result = $qb->execute();
        $row = $result->fetch();

        // Return the total count
        return (int)$row['count'];
    }

	/**
	 * Get the last call log.
	 *
	 * @return CallLog|null The last call log or null if no logs exist.
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
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
    }

	/**
	 * Get call statistics grouped by date for a specific date range
	 *
	 * @param DateTime $from Start date
	 * @param DateTime $to End date
	 *
	 * @return array Array of daily statistics with success and error counts
	 * @throws Exception
	 */
    public function getCallStatsByDateRange(DateTime $from, DateTime $to): array
    {
        $qb = $this->db->getQueryBuilder();

        // Get the actual data from database
        $qb->select(
                $qb->createFunction('DATE(created) as date'),
                $qb->createFunction('SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as success'),
                $qb->createFunction('SUM(CASE WHEN status_code < 200 OR status_code >= 300 THEN 1 ELSE 0 END) as error')
            )
            ->from('openconnector_call_logs')
            ->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
            ->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
            ->groupBy('date')
            ->orderBy('date', 'ASC');

        $result = $qb->execute();
        $stats = [];

        // Create DatePeriod to iterate through all dates
        $period = new DatePeriod(
            $from,
            new DateInterval('P1D'),
            $to->modify('+1 day')
        );

        // Initialize all dates with zero values
        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');
            $stats[$dateStr] = [
                'success' => 0,
                'error' => 0
            ];
        }

        // Fill in actual values where they exist
        while ($row = $result->fetch()) {
            $stats[$row['date']] = [
                'success' => (int)$row['success'],
                'error' => (int)$row['error']
            ];
        }

        return $stats;
    }

	/**
	 * Get call statistics grouped by hour for a specific date range
	 *
	 * @param DateTime $from Start date
	 * @param DateTime $to End date
	 *
	 * @return array Array of hourly statistics with success and error counts
	 * @throws Exception
	 */
    public function getCallStatsByHourRange(DateTime $from, DateTime $to): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
                $qb->createFunction('HOUR(created) as hour'),
                $qb->createFunction('SUM(CASE WHEN status_code >= 200 AND status_code < 300 THEN 1 ELSE 0 END) as success'),
                $qb->createFunction('SUM(CASE WHEN status_code < 200 OR status_code >= 300 THEN 1 ELSE 0 END) as error')
            )
            ->from('openconnector_call_logs')
            ->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
            ->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');

        $result = $qb->execute();
        $stats = [];

        while ($row = $result->fetch()) {
            $stats[$row['hour']] = [
                'success' => (int)$row['success'],
                'error' => (int)$row['error']
            ];
        }

        return $stats;
    }

    /**
     * Get the total count of all call logs matching the given filters.
     *
     * @param array $filters
     * @return int
     */
    public function getTotalCount(array $filters = []): int
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_call_logs');

        foreach ($filters as $filter => $value) {
            if ($value === 'IS NOT NULL') {
                $qb->andWhere($qb->expr()->isNotNull($filter));
            } elseif ($value === 'IS NULL') {
                $qb->andWhere($qb->expr()->isNull($filter));
            } else {
                $qb->andWhere($qb->expr()->eq($filter, $qb->createNamedParameter($value)));
            }
        }

        $result = $qb->execute();
        $row = $result->fetch();

        return (int)$row['count'];
    }

	/**
	 * Calculate the approximate size of a call log entry.
	 *
	 * This method estimates the size by summing the length of all text and JSON fields.
	 *
	 * @param CallLog $log The log entry to calculate size for
	 *
	 * @return int The estimated size in bytes
	 */
	private function calculateLogSize(CallLog $log): int
	{
		$size = 0;
		
		// Add size of string fields
		$size += strlen($log->getUuid() ?? '');
		$size += strlen($log->getStatusMessage() ?? '');
		$size += strlen($log->getUserId() ?? '');
		$size += strlen($log->getSessionId() ?? '');
		
		// Add size of JSON fields (request and response arrays)
		$request = $log->getRequest();
		if (!empty($request)) {
			$size += strlen(json_encode($request));
		}
		
		$response = $log->getResponse();
		if (!empty($response)) {
			$size += strlen(json_encode($response));
		}
		
		// Add approximate size of other fields (IDs, status code, dates)
		$size += 100; // Rough estimate for integers and datetime fields
		
		return $size;
	}//end calculateLogSize()

	/**
	 * Count call logs with optional filters.
	 *
	 * This method provides flexible filtering capabilities similar to findAll
	 * but returns only the count of matching records.
	 *
	 * @param array $filters Optional filters to apply
	 *
	 * @return int The count of logs matching the filters
	 *
	 * @throws \OCP\DB\Exception Database operation exceptions
	 */
	public function count(array $filters = []): int
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->count('*'))
			->from('openconnector_call_logs');

		// Apply filters
		foreach ($filters as $filter => $value) {
			if ($value === 'IS NOT NULL') {
				$qb->andWhere($qb->expr()->isNotNull($filter));
			} elseif ($value === 'IS NULL') {
				$qb->andWhere($qb->expr()->isNull($filter));
			} elseif (is_array($value)) {
				// Handle array values like ['IS NULL', '']
				$conditions = [];
				foreach ($value as $val) {
					if ($val === 'IS NULL') {
						$conditions[] = $qb->expr()->isNull($filter);
					} elseif ($val === 'IS NOT NULL') {
						$conditions[] = $qb->expr()->isNotNull($filter);
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

		$result = $qb->executeQuery();
		$row = $result->fetch();

		return (int)($row['COUNT(*)'] ?? 0);
	}//end count()

	/**
	 * Calculate total size of call logs with optional filters.
	 *
	 * This method sums the size field of logs matching the given filters,
	 * useful for storage management and retention analysis.
	 *
	 * @param array $filters Optional filters to apply
	 *
	 * @return int The total size of logs matching the filters in bytes
	 *
	 * @throws \OCP\DB\Exception Database operation exceptions
	 */
	public function size(array $filters = []): int
	{
		$qb = $this->db->getQueryBuilder();

		$qb->select($qb->func()->sum('size'))
			->from('openconnector_call_logs');

		// Apply filters
		foreach ($filters as $filter => $value) {
			if ($value === 'IS NOT NULL') {
				$qb->andWhere($qb->expr()->isNotNull($filter));
			} elseif ($value === 'IS NULL') {
				$qb->andWhere($qb->expr()->isNull($filter));
			} elseif (is_array($value)) {
				// Handle array values like ['IS NULL', '']
				$conditions = [];
				foreach ($value as $val) {
					if ($val === 'IS NULL') {
						$conditions[] = $qb->expr()->isNull($filter);
					} elseif ($val === 'IS NOT NULL') {
						$conditions[] = $qb->expr()->isNotNull($filter);
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

		$result = $qb->executeQuery();
		$row = $result->fetch();

		return (int)($row['SUM(size)'] ?? 0);
	}//end size()

	/**
	 * Set expiry dates for call logs based on retention period in milliseconds.
	 *
	 * Updates the expires column for call logs based on their creation date plus the retention period.
	 * Only affects call logs that don't already have an expiry date set.
	 *
	 * @param int $retentionMs Retention period in milliseconds
	 *
	 * @return int Number of call logs updated
	 *
	 * @throws \Exception Database operation exceptions
	 *
	 * @psalm-return int
	 * @phpstan-return int
	 */
	public function setExpiryDate(int $retentionMs): int
	{
		try {
			// Convert milliseconds to seconds for DateTime calculation
			$retentionSeconds = intval($retentionMs / 1000);
			
			// Get the query builder
			$qb = $this->db->getQueryBuilder();
			
			// Update call logs that don't have an expiry date set
			$qb->update('openconnector_call_logs')
			   ->set('expires', $qb->createFunction(
				   sprintf('DATE_ADD(created, INTERVAL %d SECOND)', $retentionSeconds)
			   ))
			   ->where($qb->expr()->isNull('expires'));
			
			// Execute the update and return number of affected rows
			return $qb->executeStatement();
		} catch (\Exception $e) {
			// Log the error for debugging purposes
			\OC::$server->getLogger()->error('Failed to set expiry dates for call logs: ' . $e->getMessage(), [
				'app' => 'openconnector',
				'exception' => $e
			]);
			
			// Re-throw the exception so the caller knows something went wrong
			throw $e;
		}
	}//end setExpiryDate()
}
