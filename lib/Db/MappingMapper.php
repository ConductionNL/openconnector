<?php
/**
 * OpenConnector Mapping Mapper
 *
 * This file contains the mapper class for mapping data in the OpenConnector
 * application.
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

use OCA\OpenConnector\Db\Mapping;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class MappingMapper
 *
 * This class is responsible for mapping Mapping entities to the database.
 * It provides methods for finding, creating, and updating Mapping objects.
 *
 * @package OCA\OpenConnector\Db
 */
class MappingMapper extends QBMapper
{


    /**
     * MappingMapper constructor.
     *
     * @param IDBConnection $db The database connection
     *
     * @return void
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_mappings');

    }//end __construct()


    /**
     * Find a Mapping by its ID.
     *
     * @param int $id The ID of the Mapping
     *
     * @return Mapping The found Mapping entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the mapping is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one mapping is found
     */
    public function find(int $id): Mapping
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_mappings')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity(query: $qb);

    }//end find()


    /**
     * Find mappings by reference.
     *
     * @param string $reference The reference identifier to search for
     *
     * @return array An array of Mapping entities matching the reference
     */
    public function findByRef(string $reference): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_mappings')
            ->where(
                $qb->expr()->eq('reference', $qb->createNamedParameter($reference))
            );

        return $this->findEntities(query: $qb);

    }//end findByRef()


    /**
     * Find all Mappings with optional filtering and pagination.
     *
     * @param int|null   $limit            Maximum number of results to return
     * @param int|null   $offset           Number of results to skip
     * @param array|null $filters          Associative array of filters
     * @param array|null $searchConditions Array of search conditions
     * @param array|null $searchParams     Array of search parameters
     *
     * @return array An array of Mapping entities
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
            ->from('openconnector_mappings')
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
     * Create a new Mapping from an array of data.
     *
     * @param array $object An array of Mapping data
     *
     * @return Mapping The newly created Mapping entity
     */
    public function createFromArray(array $object): Mapping
    {
        $obj = new Mapping();
        $obj->hydrate($object);

        // Set uuid.
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set version.
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }

        return $this->insert(entity: $obj);

    }//end createFromArray()


    /**
     * Update an existing Mapping from an array of data.
     *
     * @param int   $id     The ID of the Mapping to update
     * @param array $object An array of updated Mapping data
     *
     * @return Mapping The updated Mapping entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the mapping is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one mapping is found
     */
    public function updateFromArray(int $id, array $object): Mapping
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

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Get the total count of all mappings.
     *
     * @return int The total number of mappings in the database
     */
    public function getTotalCallCount(): int
    {
        $qb = $this->db->getQueryBuilder();

        // Select count of all mappings.
        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_mappings');

        $result = $qb->execute();
        $row    = $result->fetch();

        // Return the total count.
        return (int) $row['count'];

    }//end getTotalCallCount()


}//end class
