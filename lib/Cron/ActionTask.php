<?php

namespace OCA\OpenConnector\Cron;

use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\JobLog;
use OCA\OpenConnector\Db\JobLogMapper;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Uid\Uuid;
use DateInterval;
use DateTime;

/**
 * This class is used to run the action tasks for the OpenConnector app. It hooks into the cron job list and runs the classes that are set as the job class in the job.
 *
 * @package OCA\OpenConnector\Cron
 */
class ActionTask extends TimedJob
{
    private JobMapper $jobMapper;
    private JobLogMapper $jobLogMapper;
    private IJobList $jobList;
    private ContainerInterface $containerInterface;

    public function __construct(
        ITimeFactory $time,
        JobMapper $jobMapper,
        JobLogMapper $jobLogMapper,
        IJobList $jobList,
        ContainerInterface $containerInterface,
		private IUserSession $userSession,
		private IUserManager $userManager,
    ) {
        parent::__construct($time);
        $this->jobMapper = $jobMapper;
        $this->jobLogMapper = $jobLogMapper;
        $this->jobList = $jobList;
        $this->containerInterface = $containerInterface;
        // Run every 5 minutes
        //$this->setInterval(300);

        // Delay until low-load time
        //$this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_SENSITIVE);
        // Or $this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_INSENSITIVE);

        // Only run one instance of this job at a time
        //$this->setAllowParallelRuns(false);
    }

	/**
	 * @todo
	 * @todo: make this a bit more generic :')
	 *
	 * @param $argument
	 *
	 * @return JobLog|void
	 * @throws \OCP\DB\Exception
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
    public function run($argument)
    {
        // if we do not have a job id then everything is wrong
        if (isset($argument['jobId']) === false || is_int($argument['jobId']) === false) {
			return $this->jobLogMapper->createFromArray([
				'jobId'         => 'null',
				'level'			=> 'ERROR',
				'message'		=> "Couldn't find a jobId in the action argument"
			]);
        }

        // Let's get the job, the user might have deleted it in the meantime
        try {
            $job = $this->jobMapper->find($argument['jobId']);
        } catch (Exception $e) {
            return $this->jobLogMapper->createFromArray([
				'jobId'         => $argument['jobId'],
				'level'			=> 'ERROR',
				'message'		=> "Couldn't find a Job with this jobId, message: ".$e->getMessage()
			]);
        }

		$forceRun = false;
		$stackTrace = [];
		if (isset($argument['forceRun']) === true && $argument['forceRun'] === true) {
			$forceRun = true;
			$stackTrace[] = 'Doing a force run for this job, ignoring "enabled" & "nextRun" check...';
		}

        // If the job is not enabled, we don't need to do anything
        if ($forceRun === false && $job->getIsEnabled() === false) {
			return $this->jobLogMapper->createForJob($job, [
				'level'			=> 'WARNING',
				'message'		=> 'This job is disabled'
			]);
        }

        // if the next run is in the future, we don't need to do anything
        if ($forceRun === false && $job->getNextRun() !== null && $job->getNextRun() > new DateTime()) {
			return $this->jobLogMapper->createForJob($job, [
				'level'			=> 'WARNING',
				'message'		=> 'Next Run is still in the future for this job'
			]);
        }

		if (empty($job->getUserId()) === false && $this->userSession->getUser() === null) {
			$user = $this->userManager->get($job->getUserId());
			$this->userSession->setUser($user);
		}

		$time_start = microtime(true);

        $action =  $this->containerInterface->get($job->getJobClass());
        $arguments = $job->getArguments();
        if (is_array($arguments) === false) {
            $arguments = [];
        }
        $result = $action->run($arguments);

        $time_end = microtime(true);
        $executionTime = ( $time_end - $time_start ) * 1000;

        // deal with single run
        if ($forceRun === false && $job->isSingleRun() === true) {
            $job->setIsEnabled(false);
        }

        // Update the job
		$job->setLastRun(new DateTime());
		if ($forceRun === false) {
			$nextRun = new DateTime('now + '.$job->getInterval().' seconds');
			if (isset($result['nextRun']) === true) {
				$nextRun = DateTime::createFromFormat('U', $result['nextRun'], $nextRun->getTimezone());
				// Check if the current seconds part is not zero, and if so, round up to the next minute
				if ($nextRun->format('s') !== '00') {
					$nextRun->modify('next minute');
				}
			}
			$nextRun->setTime(hour: $nextRun->format('H'), minute: $nextRun->format('i'));
			$job->setNextRun($nextRun);
		}
		$this->jobMapper->update($job);

        // Log the job
        $jobLog = $this->jobLogMapper->createForJob($job, [
			'level'			=> 'INFO',
			'message'		=> 'Succes',
            'executionTime' => $executionTime
        ]);

        // Get the result and set it to the job log
        if (is_array($result) === true) {
            if (isset($result['level']) === true) {
                $jobLog->setLevel($result['level']);
            }
            if (isset($result['message']) === true) {
                $jobLog->setMessage($result['message']);
            }
            if (isset($result['stackTrace']) === true) {
				$stackTrace = array_merge($stackTrace, $result['stackTrace']);
            }
        }

		$jobLog->setStackTrace($stackTrace);

		$this->jobLogMapper->update(entity: $jobLog);

        // Let's report back about what we have just done
        return $jobLog;
    }

}
