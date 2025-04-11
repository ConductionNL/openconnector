<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\Endpoint;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Mapper class for handling Endpoint database operations
 *
 * @package OCA\OpenConnector\Db
 */
class EndpointMapper extends \OCA\OpenConnector\Db\BaseMapper
{
    /**
     * The name of the database table for endpoints
     */
    private const TABLE_NAME = 'openconnector_endpoints';


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
     * Create a new Endpoint entity instance
     *
     * @return Endpoint A new Endpoint instance
     */
    protected function createEntity(): Entity
    {
        return new Endpoint();

    }//end createEntity()


    private function createEndpointRegex(string $endpoint): string
    {
        $regex = '#^'.preg_replace(
            [
                '#\/{{([^}}]+)}}\/#',
                '#\/{{([^}}]+)}}$#',
            ],
            [
                '/([^/]+)/',
                '(/([^/]+))?',
            ],
            $endpoint
        ).'#';

        // Replace only the LAST occurrence of "(/([^/]+))?#" with "(?:/([^/]+))?$#"
        $regex = preg_replace_callback(
            '/\(\/\(\[\^\/\]\+\)\)\?#/',
            function ($matches) {
                return '(?:/([^/]+))?$#';
            },
            $regex,
            1
            // Limit to only one replacement
        );

        if (str_ends_with($regex, '?#') === false && str_ends_with($regex, '$#') === false) {
            $regex = substr($regex, 0, -1).'$#';
        }

        return $regex;

    }//end createEndpointRegex()


    public function createFromArray(array $object): Endpoint
    {
        $obj = new Endpoint();
        $obj->hydrate($object);

        // Set uuid
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set version
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }

        // Endpoint-specific logic
        $obj->setEndpointRegex($this->createEndpointRegex($obj->getEndpoint()));
        $obj->setEndpointArray(explode('/', $obj->getEndpoint()));

        return $this->insert(entity: $obj);

    }//end createFromArray()


    public function updateFromArray(int $id, array $object): Endpoint
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

        // Endpoint-specific logic
        $obj->setEndpointRegex($this->createEndpointRegex($obj->getEndpoint()));
        $obj->setEndpointArray(explode('/', $obj->getEndpoint()));

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Find endpoints that match a given path and method using regex comparison
     *
     * @param  string $path   The path to match against endpoint regex patterns
     * @param  string $method The HTTP method to filter by (GET, POST, etc)
     * @return array Array of matching Endpoint entities
     */
    public function findByPathRegex(string $path, string $method): array
    {
        // Get all endpoints first since we need to do regex comparison
        $endpoints = $this->findAll();

        // Filter endpoints where both path matches regex pattern and method matches
        return array_filter(
            $endpoints,
            function (Endpoint $endpoint) use ($path, $method) {
                // Get the regex pattern from the endpoint
                $pattern = $endpoint->getEndpointRegex();

                // Skip if no regex pattern is set
                if (empty($pattern) === true) {
                    return false;
                }

                // Check if both path matches the regex pattern and method matches
                return preg_match($pattern, $path) === 1 &&
                       $endpoint->getMethod() === $method;
            }
        );

    }//end findByPathRegex()


    /**
     * Find endpoints that are linked to a specific register
     *
     * @param int $registerId The ID of the register to find endpoints for
     *
     * @return array<Endpoint> Array of Endpoint entities linked to the register
     */
    public function getByRegister(int $registerId): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from($this->getTableName())
            ->where(
                $qb->expr()->andX(
                    $qb->expr()->eq('target_type', $qb->createNamedParameter('register/schema')),
                    // Use LIKE to match the part before the '/' in target_id
                    $qb->expr()->like('target_id', $qb->createNamedParameter($registerId . '/%'))
                )
            );

        return $this->findEntities($qb);

    }//end getByRegister()


    /**
     * Find an endpoint by ID
     *
     * @param int $id The ID of the endpoint to find
     * @return Endpoint The found endpoint entity
     * @throws DoesNotExistException If the endpoint doesn't exist
     * @throws MultipleObjectsReturnedException If multiple endpoints match the criteria
     */
    public function find(int $id): Endpoint
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
     * Find an endpoint by UUID
     *
     * @param string $uuid The UUID of the endpoint to find
     * @return Endpoint The found endpoint entity
     * @throws DoesNotExistException If the endpoint doesn't exist
     * @throws MultipleObjectsReturnedException If multiple endpoints match the criteria
     */
    public function findByUuid(string $uuid): Endpoint
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
     * Find all endpoints with optional filtering and pagination
     *
     * @param int|null $limit Maximum number of results to return
     * @param int|null $offset Number of results to skip
     * @param array|null $filters Associative array of filter conditions (column => value)
     * @param array|null $searchConditions Search conditions for the query
     * @param array|null $searchParams Parameters for the search conditions
     * @param array|null $ids List of IDs or UUIDs to search for
     * @return array<Endpoint> Array of matching endpoint entities
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
