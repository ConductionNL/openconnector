<?php
/**
 * OpenConnector Source Mapper
 *
 * This file contains the mapper class for source data in the OpenConnector
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

use OCA\OpenConnector\Db\Source;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

/**
 * Class SourceMapper
 *
 * This class is responsible for mapping Source entities to the database.
 * It provides methods for finding, creating, and updating Source objects.
 *
 * @package OCA\OpenConnector\Db
 */
class SourceMapper extends QBMapper
{


    /**
     * SourceMapper constructor.
     *
     * @param IDBConnection $db The database connection
     *
     * @return void
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_sources');

    }//end __construct()


    /**
     * Find a Source by its ID.
     *
     * @param int $id The ID of the Source
     *
     * @return Source The found Source entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the source is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one source is found
     */
    public function find(int $id): Source
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_sources')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity(query: $qb);

    }//end find()


    /**
     * Find sources by reference.
     *
     * @param string $reference The reference identifier to search for
     *
     * @return array An array of Source entities matching the reference
     */
    public function findByRef(string $reference): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_sources')
            ->where(
                $qb->expr()->eq('reference', $qb->createNamedParameter($reference))
            );

        return $this->findEntities(query: $qb);

    }//end findByRef()


    /**
     * Find all Sources with optional filtering and pagination.
     *
     * @param int|null   $limit            Maximum number of results to return
     * @param int|null   $offset           Number of results to skip
     * @param array|null $filters          Associative array of filters
     * @param array|null $searchConditions Array of search conditions
     * @param array|null $searchParams     Array of search parameters
     *
     * @return array An array of Source entities
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
            ->from('openconnector_sources')
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
     * Create a new Source from an array of data.
     *
     * @param array $object An array of Source data
     *
     * @return Source The newly created Source entity
     */
    public function createFromArray(array $object): Source
    {
        $obj = new Source();
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
     * Update an existing Source from an array of data.
     *
     * @param int   $id     The ID of the Source to update
     * @param array $object An array of updated Source data
     *
     * @return Source The updated Source entity
     * @throws \OCP\AppFramework\Db\DoesNotExistException If the source is not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException If more than one source is found
     */
    public function updateFromArray(int $id, array $object): Source
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
     * Get the total count of all sources.
     *
     * @return int The total number of sources in the database
     */
    public function getTotalCallCount(): int
    {
        $qb = $this->db->getQueryBuilder();

        // Select count of all sources.
        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_sources');

        $result = $qb->execute();
        $row    = $result->fetch();

        // Return the total count.
        return (int) $row['count'];

    }//end getTotalCallCount()


}//end class
