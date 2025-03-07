<?php

namespace OCA\OpenConnector\Cron;

use OCA\OpenConnector\Db\CallLogMapper;
use OCA\OpenConnector\Db\JobLogMapper;
use OCA\OpenConnector\Db\JobMapper;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUserManager;
use OCP\IUserSession;

class LogCleanUpTask extends TimedJob
{
	public function __construct(
		ITimeFactory $time,
		private readonly CallLogMapper $callLogMapper,
	) {
		parent::__construct($time);

		// Run every 5 minutes
		$this->setInterval(300);

		// Delay until low-load time
		//$this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_SENSITIVE);
		// Or $this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_INSENSITIVE);

		// Only run one instance of this job at a time
		$this->setAllowParallelRuns(false);
	}

    public function run(mixed $argument) {
        $this->callLogMapper->clearLogs();
    }

}
