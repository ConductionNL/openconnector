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

use OCA\OpenConnector\Db\Endpoint;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Mapper class for handling Endpoint database operations
 */
class EndpointMapper extends QBMapper
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
        parent::__construct($db, 'openconnector_endpoints');

    }//end __construct()


    /**
     * Find an endpoint by ID
     *
     * @param         int $id Endpoint ID
     * @psalm-param   int $id
     * @phpstan-param int $id
     * @return        Endpoint Found endpoint
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the endpoint doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple endpoints match
     */
    public function find(int $id): Endpoint
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_endpoints')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity(query: $qb);

    }//end find()


    /**
     * Find endpoints by reference
     *
     * @param          string $reference Endpoint reference
     * @psalm-param    string $reference
     * @phpstan-param  string $reference
     * @return         Endpoint[] Array of endpoints matching the reference
     * @psalm-return   array<int, Endpoint>
     * @phpstan-return array<int, Endpoint>
     */
    public function findByRef(string $reference): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_endpoints')
            ->where(
                $qb->expr()->eq('reference', $qb->createNamedParameter($reference))
            );

        return $this->findEntities(query: $qb);

    }//end findByRef()


    /**
     * Find all endpoints with optional filtering
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
     * @return         Endpoint[] Array of endpoints
     * @psalm-return   array<int, Endpoint>
     * @phpstan-return array<int, Endpoint>
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
            ->from('openconnector_endpoints')
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
     * Create a regex pattern from an endpoint path
     *
     * @param         string $endpoint The endpoint path
     * @psalm-param   string $endpoint
     * @phpstan-param string $endpoint
     * @return        string The generated regex pattern
     */
    private function createEndpointRegex(string $endpoint): string
    {
        $regex = '#'.preg_replace(
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

        // Replace only the LAST occurrence of "(/([^/]+))?#" with "(?:/([^/]+))?$#".
        $regex = preg_replace_callback(
            '/\(\/\(\[\^\/\]\+\)\)\?#/',
            function ($matches) {
                return '(?:/([^/]+))?$#';
            },
            $regex,
            1
            // Limit to only one replacement.
        );

        if (str_ends_with($regex, '?#') === false && str_ends_with($regex, '$#') === false) {
            $regex = substr($regex, 0, -1).'$#';
        }

        return $regex;

    }//end createEndpointRegex()


    /**
     * Create a new endpoint from array data
     *
     * @param         array $object Endpoint data
     * @psalm-param   array<string, mixed> $object
     * @phpstan-param array<string, mixed> $object
     * @return        Endpoint Created endpoint
     */
    public function createFromArray(array $object): Endpoint
    {
        $obj = new Endpoint();
        $obj->hydrate($object);

        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set version.
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }

        // Endpoint-specific logic.
        $obj->setEndpointRegex($this->createEndpointRegex($obj->getEndpoint()));
        $obj->setEndpointArray(explode('/', $obj->getEndpoint()));

        return $this->insert(entity: $obj);

    }//end createFromArray()


    /**
     * Update an existing endpoint from array data
     *
     * @param         int   $id     Endpoint ID
     * @param         array $object Updated endpoint data
     * @psalm-param   int $id
     * @psalm-param   array<string, mixed> $object
     * @phpstan-param int $id
     * @phpstan-param array<string, mixed> $object
     * @return        Endpoint Updated endpoint
     * @throws        \OCP\AppFramework\Db\DoesNotExistException If the endpoint doesn't exist
     * @throws        \OCP\AppFramework\Db\MultipleObjectsReturnedException If multiple endpoints match
     */
    public function updateFromArray(int $id, array $object): Endpoint
    {
        $obj = $this->find($id);

        // Set version.
        if (empty($obj->getVersion()) === true) {
            $object['version'] = '0.0.1';
        } else if (empty($object['version']) === true) {
            // Update version.
            $version = explode('.', $obj->getVersion());
            if (isset($version[2]) === true) {
                $version[2]        = ((int) $version[2] + 1);
                $object['version'] = implode('.', $version);
            }
        }

        $obj->hydrate($object);

        // Endpoint-specific logic.
        $obj->setEndpointRegex($this->createEndpointRegex($obj->getEndpoint()));
        $obj->setEndpointArray(explode('/', $obj->getEndpoint()));

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Get the total count of all endpoints
     *
     * @return int The total number of endpoints in the database
     */
    public function getTotalCount(): int
    {
        $qb = $this->db->getQueryBuilder();

        // Select count of all endpoints.
        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_endpoints');

        $result = $qb->execute();
        $row    = $result->fetch();

        // Return the total count.
        return (int) $row['count'];

    }//end getTotalCount()


    /**
     * Find endpoints that match a given path and method using regex comparison
     *
     * @param          string $path   The path to match against endpoint regex patterns
     * @param          string $method The HTTP method to filter by (GET, POST, etc)
     * @psalm-param    string $path
     * @psalm-param    string $method
     * @phpstan-param  string $path
     * @phpstan-param  string $method
     * @return         Endpoint[] Array of matching Endpoint entities
     * @psalm-return   array<int, Endpoint>
     * @phpstan-return array<int, Endpoint>
     */
    public function findByPathRegex(string $path, string $method): array
    {
        // Get all endpoints first since we need to do regex comparison.
        $endpoints = $this->findAll();

        // Filter endpoints where both path matches regex pattern and method matches.
        return array_filter(
            $endpoints,
            function (Endpoint $endpoint) use ($path, $method) {
                // Get the regex pattern from the endpoint.
                $pattern = $endpoint->getEndpointRegex();

                // Skip if no regex pattern is set.
                if (empty($pattern) === true) {
                    return false;
                }

                // Check if both path matches the regex pattern and method matches.
                return preg_match($pattern, $path) === 1 &&
                   $endpoint->getMethod() === $method;
            }
        );

    }//end findByPathRegex()


}//end class
