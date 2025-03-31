<?php
/**
 * OpenConnector Synchronization Action
 *
 * This file contains the action class for handling synchronization operations
 * in the OpenConnector application.
 *
 * @category  Action
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Action;

use Exception;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * This action handles the synchronization of data from the source to the target.
 *
 * @package OCA\OpenConnector\Action
 */
class SynchronizationAction
{

    /**
     * Synchronization service for handling synchronizations
     *
     * @var SynchronizationService
     */
    private SynchronizationService $synchronizationService;

    /**
     * Synchronization mapper for database operations
     *
     * @var SynchronizationMapper
     */
    private SynchronizationMapper $synchronizationMapper;

    /**
     * Synchronization contract mapper for database operations
     *
     * @var SynchronizationContractMapper
     */
    private SynchronizationContractMapper $synchronizationContractMapper;


    /**
     * Constructor for the SynchronizationAction class
     *
     * @param SynchronizationService        $synchronizationService        Service for handling synchronizations
     * @param SynchronizationMapper         $synchronizationMapper         Mapper for synchronization operations
     * @param SynchronizationContractMapper $synchronizationContractMapper Mapper for contract operations
     *
     * @return void
     */
    public function __construct(
        SynchronizationService $synchronizationService,
        SynchronizationMapper $synchronizationMapper,
        SynchronizationContractMapper $synchronizationContractMapper,
    ) {
        $this->synchronizationService        = $synchronizationService;
        $this->synchronizationMapper         = $synchronizationMapper;
        $this->synchronizationContractMapper = $synchronizationContractMapper;

    }//end __construct()


    /**
     * Executes the synchronization process based on the provided arguments.
     * This method checks for a valid synchronization ID, processes a synchronization contract if provided,
     * or performs a general synchronization action. It returns a stack trace of operations performed.
     *
     * @param array $argument An array of arguments that can include 'synchronizationId' and 'synchronizationContractId'.
     *
     * @todo Make this method more generic to handle different synchronization processes.
     * @todo Implement proper error handling when 'synchronizationId' is missing or invalid.
     * @todo Improve handling for testing purposes and synchronization contract logic.
     *
     * @return array Returns an array containing the stack trace of actions performed and any warnings or messages.
     *
     * @throws Exception Throws an exception if the synchronization process fails or encounters an error.
     */
    public function run(array $argument=[]): array
    {

        $response = [];

        // If we do not have a synchronization Id then everything is wrong.
        $response['message'] = $response['stackTrace'][] = 'Check for a valid synchronization ID';
        if (isset($argument['synchronizationId']) === false) {
            // @todo: implement error handling.
            $response['level']        = 'ERROR';
            $response['stackTrace'][] = $response['message'] = 'No synchronization ID provided';

            return $response;
        }

        // Let's find a synchronysation.
        $response['stackTrace'][] = 'Getting synchronization: '.$argument['synchronizationId'];
        $synchronization          = $this->synchronizationMapper->find((int) $argument['synchronizationId']);
        if ($synchronization === null) {
            $response['level']        = 'WARNING';
            $response['stackTrace'][] = $response['message'] = 'Synchronization not found: '.$argument['synchronizationId'];
            return $response;
        }

        // Doing the synchronization.
        $response['stackTrace'][] = 'Doing the synchronization';
        try {
            $objects = $this->synchronizationService->synchronize($synchronization);
        } catch (TooManyRequestsHttpException $e) {
            $response['level']        = 'WARNING';
            $response['stackTrace'][] = $response['message'] = 'Stopped synchronization: '.$e->getMessage();

            // Handle rate limiting headers if present.
            if (isset($e->getHeaders()['X-RateLimit-Reset']) === true) {
                $response['nextRun']      = $e->getHeaders()['X-RateLimit-Reset'];
                $response['stackTrace'][] = 'Returning X-RateLimit-Reset header to update Job nextRun: '.$response['nextRun'];
            }

            return $response;
        } catch (Exception $e) {
            $response['level']        = 'ERROR';
            $response['stackTrace'][] = $response['message'] = 'Failed to synchronize: '.$e->getMessage();
            return $response;
        }

        $response['level'] = 'INFO';

        $objectCount = 0;
        if (is_array($objects) === true) {
            // Calculate object count from response.
            if (isset($objects['result']['contracts']) === true && $objects['result']['contracts'] === true) {
                $objectCount = count($objects['result']['contracts']);
            } else if (isset($objects['result']['objects']['found']) === true) {
                $objectCount = $objects['result']['objects']['found'];
            }
        }

        $response['stackTrace'][] = $response['message'] = 'Synchronized '.$objectCount.' successfully';

        // Let's report back about what we have just done.
        return $response;

    }//end run()


}//end class
