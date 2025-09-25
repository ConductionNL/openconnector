<?php

namespace OCA\OpenConnector\Action;

use Exception;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * This action handles the synchronization of data from the source to the target.
 *
 * @package OCA\OpenConnector\Cron
 */
class SynchronizationAction
{
    private SynchronizationService $synchronizationService;
    private SynchronizationMapper $synchronizationMapper;
    private SynchronizationContractMapper $synchronizationContractMapper;
    public function __construct(
        SynchronizationService $synchronizationService,
        SynchronizationMapper $synchronizationMapper,
        SynchronizationContractMapper $synchronizationContractMapper,
    ) {
        $this->synchronizationService = $synchronizationService;
        $this->synchronizationMapper = $synchronizationMapper;
        $this->synchronizationContractMapper = $synchronizationContractMapper;
    }

	/**
	 * Executes the synchronization process based on the provided arguments.
	 * This method checks for a valid synchronization ID, processes a synchronization contract if provided,
	 * or performs a general synchronization action. It returns a stack trace of operations performed.
	 *
	 * @todo Make this method more generic to handle different synchronization processes.
	 * @todo Implement proper error handling when 'synchronizationId' is missing or invalid.
	 * @todo Improve handling for testing purposes and synchronization contract logic.
	 *
	 * @param array $argument An array of arguments that can include 'synchronizationId' and 'synchronizationContractId'.
	 *
	 * @return array Returns an array containing the stack trace of actions performed and any warnings or messages.
	 *
	 * @throws Exception Throws an exception if the synchronization process fails or encounters an error.
	 */
    public function run(array $argument = []): array
	{

        $response = [];

        // if we do not have a synchronization Id then everything is wrong
        $response['message'] = $response['stackTrace'][] = 'Check for a valid synchronization ID';
        if (isset($argument['synchronizationId']) === false) {
            // @todo: implement error handling
            $response['level'] = 'ERROR';
			$response['stackTrace'][] = $response['message'] = 'No synchronization ID provided';

            return $response;
        }

        // Let's find a synchronysation
        $response['stackTrace'][] = 'Getting synchronization: '.$argument['synchronizationId'];
        try {
            $synchronization = $this->synchronizationMapper->find((int) $argument['synchronizationId']);
        } catch (\OCP\AppFramework\Db\DoesNotExistException $e) {
            $response['level'] = 'WARNING';
			$response['stackTrace'][] = $response['message'] = 'Synchronization not found: '.(string)$argument['synchronizationId'];
            return $response;
        }

        // Doing the synchronization
        $response['stackTrace'][] = 'Doing the synchronization';
        try {
            $objects = $this->synchronizationService->synchronize($synchronization);
        } catch (TooManyRequestsHttpException $e) {
			$response['level'] = 'WARNING';
			$response['stackTrace'][] = $response['message'] = 'Stopped synchronization: ' . $e->getMessage();
			$headers = $e->getHeaders();
			if (isset($headers['X-RateLimit-Reset']) === true) {
				$response['nextRun'] = $headers['X-RateLimit-Reset'];
				$response['stackTrace'][] = 'Returning X-RateLimit-Reset header to update Job nextRun: ' . (string)$response['nextRun'];
			}
			return $response;
		} catch (Exception $e) {
            $response['level'] = 'ERROR';
			$response['stackTrace'][] = $response['message'] = 'Failed to synchronize: ' . $e->getMessage();
            return $response;
        }

        $response['level'] = 'INFO';

        $objectCount = 0;
        if (is_array($objects) === true && isset($objects['result']) && is_array($objects['result'])) {
            if (isset($objects['result']['contracts']) && is_array($objects['result']['contracts'])) {
                $objectCount = count($objects['result']['contracts']);
            } elseif (isset($objects['result']['objects']['found'])) {
                $objectCount = (int)$objects['result']['objects']['found'];
            }
        }
		$response['stackTrace'][] = $response['message'] = 'Synchronized '. $objectCount .' successfully';

        // Let's report back about what we have just done
        return $response;
    }

}
