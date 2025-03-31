<?php
/**
 * This file is part of the OpenConnector app.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service;


use OCA\OpenConnector\Cron\ActionTask;
use OCA\OpenConnector\Db\Job;
use OCA\OpenConnector\Db\JobMapper;
use OCP\BackgroundJob\IJobList;
use OCP\IDBConnection;

/**
 * Service for handling job scheduling and management.
 */
class JobService
{

    private IJobList $jobList;

    private JobMapper $jobMapper;

    private IDBConnection $connection;


    public function __construct(IJobList $jobList, JobMapper $jobMapper, ActionTask $actionTask, IDBConnection $connection)
    {

        $this->jobList    = $jobList;
        $this->jobMapper  = $jobMapper;
        $this->actionTask = $actionTask;
        $this->connection = $connection;

    }//end __construct()


    /**
     * @todo
     *
     * @param Job $job
     *
     * @return Job
     */
    public function scheduleJob(Job $job): Job
    {
        // Let's first check if the job should be disabled
        if ($job->getIsEnabled() === false || $job->getJobListId()) {
            // @todo fix this (call to protected method)
            // $this->jobList->removeById($job->getJobListId());
            // $job->setJobListId(null);
            return $this->jobMapper->update($job);
        }

        // Let's not update the job if it's already scheduled @todo we should
        if ($job->getJobListId()) {
            return $job;
        }

        // Oke this is a new job let's schedule it
        $arguments          = $job->getArguments();
        $arguments['jobId'] = $job->getId();

        if (!$job->getScheduleAfter()) {
            $this->jobList->add($this->actionTask::class, $arguments);
        } else {
            $runAfter = $job->getScheduleAfter()->getTimestamp();
            $this->jobList->scheduleAfter($this->actionTask::class, $runAfter, $arguments);
        }

        // Set the job list id
        $job->setJobListId($this->getJobListId($this->actionTask::class));
        // Save the job to the database
        return $this->jobMapper->update($job);

    }//end scheduleJob()


    /**
     * This function will get the job list id of the last job in the list
     *
     * Why the NC job list doesn't support a better way to get the last job in the list is beyond me :')
     * https://github.com/nextcloud/server/blob/master/lib/private/BackgroundJob/JobList.php#L134
     *
     * @param  class-string<IJob>|IJob $job
     * @return int|null
     */
    public function getJobListId(IJob | string $job): int | null
    {
        $class = ($job instanceof IJob) ? get_class($job) : $job;

        $query = $this->connection->getQueryBuilder();
        $query->select('id')
            ->from('jobs')
            ->where($query->expr()->eq('class', $query->createNamedParameter($class)))
            ->orderBy('id', 'DESC')
            ->setMaxResults(1);

        $result = $query->executeQuery();
        $row    = $result->fetch();
        $result->closeCursor();

        return $row['id'] ?? null;

    }//end getJobListId()


}//end class
