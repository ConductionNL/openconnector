<?php

namespace OCA\OpenConnector\Db;

use DateInterval;
use DatePeriod;
use DateTime;
use OC\Server;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

class JobLogMapper extends QBMapper
{
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_job_logs');
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

    public function find(int $id): JobLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_job_logs')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    public function findAll(?int $limit = null, ?int $offset = null, ?array $filters = [], ?array $searchConditions = [], ?array $searchParams = []): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_job_logs')
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

        return $this->findEntities($qb);
    }

	public function createForJob(Job $job, array $object): JobLog
	{
		$jobObject = [
			'jobId'         => $job->getId(),
			'jobClass'      => $job->getJobClass(),
			'jobListId'     => $job->getJobListId(),
			'arguments'     => $job->getArguments(),
			'lastRun'       => $job->getLastRun(),
			'nextRun'       => $job->getNextRun(),
		];

		$object = array_merge($jobObject, $object);

		return $this->createFromArray($object);
	}

    public function createFromArray(array $object): JobLog
    {
		if (isset($object['executionTime']) === false) {
			$object['executionTime'] = 0;
		}

        $obj = new JobLog();
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

    public function updateFromArray(int $id, array $object): JobLog
    {
        $obj = $this->find($id);
		$obj->hydrate($object);
		if ($obj->getUuid() === null) {
			$obj->setUuid(Uuid::v4());
		}

        return $this->update($obj);
    }

	/**
	 * Get the last call log.
	 *
	 * @return CallLog|null The last call log or null if no logs exist.
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
    public function getLastCallLog(): ?JobLog
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from('openconnector_job_logs')
           ->orderBy('created', 'DESC')
           ->setMaxResults(1);

        try {
            return $this->findEntity($qb);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            return null;
        }
    }

	/**
	 * Get job statistics grouped by date for a specific date range
	 *
	 * @param DateTime $from Start date
	 * @param DateTime $to End date
	 *
	 * @return array Array of daily statistics with counts per log level
	 * @throws Exception
	 */
    public function getJobStatsByDateRange(DateTime $from, DateTime $to): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
                $qb->createFunction('DATE(created) as date'),
                $qb->createFunction('SUM(CASE WHEN level = \'INFO\' THEN 1 ELSE 0 END) as info'),
                $qb->createFunction('SUM(CASE WHEN level = \'WARNING\' THEN 1 ELSE 0 END) as warning'),
                $qb->createFunction('SUM(CASE WHEN level = \'ERROR\' THEN 1 ELSE 0 END) as error'),
                $qb->createFunction('SUM(CASE WHEN level = \'DEBUG\' THEN 1 ELSE 0 END) as debug')
            )
            ->from('openconnector_job_logs')
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
                'info' => 0,
                'warning' => 0,
                'error' => 0,
                'debug' => 0
            ];
        }

        // Fill in actual values where they exist
        while ($row = $result->fetch()) {
            $stats[$row['date']] = [
                'info' => (int)$row['info'],
                'warning' => (int)$row['warning'],
                'error' => (int)$row['error'],
                'debug' => (int)$row['debug']
            ];
        }

        return $stats;
    }

	/**
	 * Get job statistics grouped by hour for a specific date range
	 *
	 * @param DateTime $from Start date
	 * @param DateTime $to End date
	 *
	 * @return array Array of hourly statistics with counts per log level
	 * @throws Exception
	 */
    public function getJobStatsByHourRange(DateTime $from, DateTime $to): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select(
                $qb->createFunction('HOUR(created) as hour'),
                $qb->createFunction('SUM(CASE WHEN level = \'INFO\' THEN 1 ELSE 0 END) as info'),
                $qb->createFunction('SUM(CASE WHEN level = \'WARNING\' THEN 1 ELSE 0 END) as warning'),
                $qb->createFunction('SUM(CASE WHEN level = \'ERROR\' THEN 1 ELSE 0 END) as error'),
                $qb->createFunction('SUM(CASE WHEN level = \'DEBUG\' THEN 1 ELSE 0 END) as debug')
            )
            ->from('openconnector_job_logs')
            ->where($qb->expr()->gte('created', $qb->createNamedParameter($from->format('Y-m-d H:i:s'))))
            ->andWhere($qb->expr()->lte('created', $qb->createNamedParameter($to->format('Y-m-d H:i:s'))))
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');

        $result = $qb->execute();
        $stats = [];

        while ($row = $result->fetch()) {
            $stats[$row['hour']] = [
                'info' => (int)$row['info'],
                'warning' => (int)$row['warning'],
                'error' => (int)$row['error'],
                'debug' => (int)$row['debug']
            ];
        }

        return $stats;
    }

    /**
     * Clear expired logs from the database
     *
     * This method deletes all job logs that have expired (i.e., their 'expires' date is earlier than the current date and time)
     * and have the 'expires' column set. This helps maintain database performance by removing old log entries that are no longer needed.
     *
     * @return bool True if any logs were deleted, false otherwise
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

            // Build the delete query to remove expired job logs that have the 'expires' column set
            $qb->delete('openconnector_job_logs')
               ->where($qb->expr()->isNotNull('expires'))
               ->andWhere($qb->expr()->lt('expires', $qb->createFunction('NOW()')));

            // Execute the query and get the number of affected rows
            $result = $qb->executeStatement();

            // Return true if any rows were affected (i.e., any logs were deleted)
            return $result > 0;
        } catch (\Exception $e) {
            // Log the error for debugging purposes
            $this->getLogger()->error('Failed to clear expired job logs: ' . $e->getMessage(), [
                'app' => 'openconnector',
                'exception' => $e
            ]);
            
            // Re-throw the exception so the caller knows something went wrong
            throw $e;
        }
    }

    /**
     * Get the total count of all job logs.
     *
     * @param array $filters Optional filters to apply
     * @return int The total number of job logs in the database.
     * @throws \OCP\DB\Exception Database operation exceptions
     *
     * @psalm-return int
     * @phpstan-return int
     */
    public function getTotalCount(array $filters = []): int
    {
        $qb = $this->db->getQueryBuilder();

        // Select count of all logs
        $qb->select($qb->createFunction('COUNT(*) as count'))
           ->from('openconnector_job_logs');

        // Apply filters if provided
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

        // Return the total count
        return (int)$row['count'];
    }

	/**
	 * Calculate the approximate size of a job log entry.
	 *
	 * This method estimates the size by summing the length of all text and JSON fields.
	 *
	 * @param JobLog $log The log entry to calculate size for
	 *
	 * @return int The estimated size in bytes
	 */
	private function calculateLogSize(JobLog $log): int
	{
		$size = 0;
		
		// Add size of string fields
		$size += strlen($log->getUuid() ?? '');
		$size += strlen($log->getLevel() ?? '');
		$size += strlen($log->getMessage() ?? '');
		$size += strlen($log->getJobId() ?? '');
		$size += strlen($log->getJobListId() ?? '');
		$size += strlen($log->getJobClass() ?? '');
		$size += strlen($log->getUserId() ?? '');
		$size += strlen($log->getSessionId() ?? '');
		
		// Add size of JSON fields (arguments and stack trace arrays)
		$arguments = $log->getArguments();
		if (!empty($arguments)) {
			$size += strlen(json_encode($arguments));
		}
		
		$stackTrace = $log->getStackTrace();
		if (!empty($stackTrace)) {
			$size += strlen(json_encode($stackTrace));
		}
		
		// Add approximate size of other fields (execution time, dates)
		$size += 100; // Rough estimate for integers and datetime fields
		
		return $size;
	}//end calculateLogSize()

	/**
	 * Count job logs with optional filters.
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
			->from('openconnector_job_logs');

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
	 * Calculate total size of job logs with optional filters.
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
			->from('openconnector_job_logs');

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
	 * Set expiry dates for job logs based on retention period in milliseconds.
	 *
	 * Updates the expires column for job logs based on their creation date plus the retention period.
	 * Only affects job logs that don't already have an expiry date set.
	 *
	 * @param int $retentionMs Retention period in milliseconds
	 *
	 * @return int Number of job logs updated
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
			
			// Update job logs that don't have an expiry date set
			$qb->update('openconnector_job_logs')
			   ->set('expires', $qb->createFunction(
				   sprintf('DATE_ADD(created, INTERVAL %d SECOND)', $retentionSeconds)
			   ))
			   ->where($qb->expr()->isNull('expires'));
			
			// Execute the update and return number of affected rows
			return $qb->executeStatement();
		} catch (\Exception $e) {
			// Log the error for debugging purposes
			$this->getLogger()->error('Failed to set expiry dates for job logs: ' . $e->getMessage(), [
				'app' => 'openconnector',
				'exception' => $e
			]);
			
			// Re-throw the exception so the caller knows something went wrong
			throw $e;
		}
	}//end setExpiryDate()
}
