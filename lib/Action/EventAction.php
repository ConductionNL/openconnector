<?php

namespace OCA\OpenConnector\Action;

use OCA\OpenConnector\Service\CallService;

/**
 * This class is used to run the action tasks for the OpenConnector app. It hooks into the cron job list and runs the classes that are set as the job class in the job.
 *
 * @package OCA\OpenConnector\Cron
 */
class EventAction
{

    private CallService $callService;


    public function __construct(
        CallService $callService,
    ) {
        $this->callService = $callService;

    }//end __construct()


    // @todo: make this a bit more generic :')


    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function run(array $argument=[]): array
    {
        // @todo: implement this
        // Let's report back about what we have just done
        return [];

    }//end run()


}//end class
