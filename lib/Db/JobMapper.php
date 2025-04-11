<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\Job;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class JobMapper
 *
 * This class is responsible for mapping Job entities to the database.
 * It provides methods for finding, creating, and updating Job objects.
 *
 * @package OCA\OpenConnector\Db
 * @extends BaseMapper<Job>
 */
class JobMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for jobs
     */
    private const TABLE_NAME = 'openconnector_jobs';


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
     * Create a new Job entity instance
     *
     * @return Job A new Job instance
     */
    protected function createEntity(): Entity
    {
        return new Job();

    }//end createEntity()


    /**
     * Find a job by ID
     *
     * @param int $id Job ID
     * @return Job The job entity
     * @throws DoesNotExistException If the job doesn't exist
     * @throws MultipleObjectsReturnedException If multiple jobs match the criteria
     */
    public function find(int $id): Job
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity($qb);
    }

    /**
     * Find a job by UUID
     *
     * @param string $uuid Job UUID
     * @return Job The job entity
     * @throws DoesNotExistException If the job doesn't exist
     * @throws MultipleObjectsReturnedException If multiple jobs match the criteria
     */
    public function findByUuid(string $uuid): Job
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->eq('uuid', $qb->createNamedParameter($uuid))
            );

        return $this->findEntity($qb);
    }

    /**
     * Find all jobs with optional filtering and pagination
     *
     * @param int|null $limit Maximum number of results to return
     * @param int|null $offset Number of results to skip
     * @param array|null $filters Associative array of filter conditions (column => value)
     * @param array|null $searchConditions Search conditions for the query
     * @param array|null $searchParams Parameters for the search conditions
     * @param array|null $ids List of IDs or UUIDs to search for
     * @return Job[] Array of matching job entities
     */
    public function findAll(
        ?int $limit=null,
        ?int $offset=null,
        ?array $filters=[],
        ?array $searchConditions=[],
        ?array $searchParams=[],
        ?array $ids=null
    ): array {
        return parent::findAll($limit, $offset, $filters, $searchConditions, $searchParams, $ids);
    }

}//end class
