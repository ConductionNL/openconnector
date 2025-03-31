<?php
/**
 * OpenConnector - Connect your Nextcloud to external services
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @category  Cron
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Cron;

use OCA\OpenConnector\Db\CallLogMapper;
use OCA\OpenConnector\Db\JobLogMapper;
use OCA\OpenConnector\Db\JobMapper;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * Background task for cleaning up old log entries
 */
class LogCleanUpTask extends TimedJob
{


    /**
     * Constructor
     *
     * @param ITimeFactory  $time          The time factory
     * @param CallLogMapper $callLogMapper The call log mapper for database operations
     *
     * @return void
     */
    public function __construct(
        ITimeFactory $time,
        private readonly CallLogMapper $callLogMapper,
    ) {
        parent::__construct($time);

        // Run every 5 minutes.
        $this->setInterval(300);

        // Delay until low-load time.
        // $this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_SENSITIVE);
        // Or $this->setTimeSensitivity(\OCP\BackgroundJob\IJob::TIME_INSENSITIVE);
        // Only run one instance of this job at a time.
        $this->setAllowParallelRuns(false);

    }//end __construct()


    /**
     * Run the cleanup task
     *
     * @param mixed $argument Arguments for the job (not used)
     *
     * @return void
     */
    public function run(mixed $argument): void
    {
        $this->callLogMapper->clearLogs();

    }//end run()


}//end class
