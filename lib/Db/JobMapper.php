<?php

namespace OCA\OpenConnector\Db;

use OCA\OpenConnector\Db\Job;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Symfony\Component\Uid\Uuid;

class JobMapper extends QBMapper
{


    public function __construct(IDBConnection $db)
    {
        parent::__construct($db, 'openconnector_jobs');

    }//end __construct()


    public function find(int $id): Job
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_jobs')
            ->where(
                $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
            );

        return $this->findEntity(query: $qb);

    }//end find()


    public function findByRef(string $reference): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_jobs')
            ->where(
                $qb->expr()->eq('reference', $qb->createNamedParameter($reference))
            );

        return $this->findEntities(query: $qb);

    }//end findByRef()


    public function findAll(?int $limit=null, ?int $offset=null, ?array $filters=[], ?array $searchConditions=[], ?array $searchParams=[]): array
    {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
            ->from('openconnector_jobs')
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


    public function createFromArray(array $object): Job
    {
        $obj = new Job();
        $obj->hydrate($object);

        // Set uuid
        if ($obj->getUuid() === null) {
            $obj->setUuid(Uuid::v4());
        }

        // Set version
        if (empty($obj->getVersion()) === true) {
            $obj->setVersion('0.0.1');
        }

        return $this->insert(entity: $obj);

    }//end createFromArray()


    public function updateFromArray(int $id, array $object): Job
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

        return $this->update($obj);

    }//end updateFromArray()


    /**
     * Get the total count of all call logs.
     *
     * @return int The total number of call logs in the database.
     */
    public function getTotalCallCount(): int
    {
        $qb = $this->db->getQueryBuilder();

        // Select count of all logs
        $qb->select($qb->createFunction('COUNT(*) as count'))
            ->from('openconnector_jobs');

        $result = $qb->execute();
        $row    = $result->fetch();

        // Return the total count
        return (int) $row['count'];

    }//end getTotalCallCount()


}//end class
