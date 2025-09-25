<?php

namespace OCA\OpenConnector\Action;

use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Db\SourceMapper;

/**
 * This class is used to run the action tasks for the OpenConnector app. It hooks into the cron job list and runs the classes that are set as the job class in the job.
 *
 * @package OCA\OpenConnector\Cron
 */
class PingAction
{
    private CallService $callService;
    private SourceMapper $sourceMapper;

    public function __construct(
        CallService $callService,
        SourceMapper $sourceMapper,
    ) {
        $this->callService = $callService;
		$this->sourceMapper = $sourceMapper;
    }

	/**
	 * Executes a simple API-call (ping / GET) on a source by using the callService.
	 * The method logs actions performed during execution and returns a stack trace of the operations.
	 *
	 * @todo Make this method more generic to support additional actions.
	 * @todo Add logging or better handling for cases when 'sourceId' is not provided.
	 *
	 * @param array $arguments An array of arguments including optional 'sourceId' to define the source for the call.
	 *
	 * @return array An array containing the execution stack trace of the actions performed.
	 */
    public function run(array $arguments = []): array
	{
		$response = [];
		$response['stackTrace'][] = 'Running PingAction';

        // For now we only have one action, so this is a bit overkill, but it's a good starting point
        if (isset($arguments['sourceId']) && is_numeric($arguments['sourceId'])) {
			$response['stackTrace'][] = "Found sourceId {$arguments['sourceId']} in arguments";
            $source = $this->sourceMapper->find((int) $arguments['sourceId']);
		}
        else {
			// @todo log and / or not default to just using the first source
			$response['stackTrace'][] = "No sourceId in arguments, default to sourceId = 1";
            $source = $this->sourceMapper->find(1);
		}

		$response['stackTrace'][] = "Calling callService...";
		$callLog = $this->callService->call($source);

		$response['stackTrace'][] = "Created callLog with id: ".$callLog->getId();

		// Let's report back about what we have just done
        return $response;
    }

}
