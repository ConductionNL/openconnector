<?php
/**
 * OpenConnector Ping Action
 *
 * This file contains the action class for handling ping-related operations
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

use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Db\SourceMapper;

/**
 * Action class that handles ping operations for API endpoints.
 *
 * @package OCA\OpenConnector\Action
 */
class PingAction
{

    /**
     * Call service for making API calls
     *
     * @var CallService
     */
    private CallService $callService;

    /**
     * Source mapper for database operations
     *
     * @var SourceMapper
     */
    private SourceMapper $sourceMapper;


    /**
     * Constructor for the PingAction class
     *
     * @param CallService  $callService  Service for making API calls
     * @param SourceMapper $sourceMapper Mapper for source operations
     *
     * @return void
     */
    public function __construct(
        CallService $callService,
        SourceMapper $sourceMapper,
    ) {
        $this->callService  = $callService;
        $this->sourceMapper = $sourceMapper;

    }//end __construct()


    /**
     * Executes a simple API-call (ping / GET) on a source by using the callService.
     * The method logs actions performed during execution and returns a stack trace of the operations.
     *
     * @param array $arguments An array of arguments including optional 'sourceId' to define the source for the call.
     *
     * @todo Make this method more generic to support additional actions.
     * @todo Add logging or better handling for cases when 'sourceId' is not provided.
     *
     * @return array An array containing the execution stack trace of the actions performed.
     */
    public function run(array $arguments=[]): array
    {
        $response = [];
        $response['stackTrace'][] = 'Running PingAction';

        // For now we only have one action, so this is a bit overkill, but it's a good starting point.
        if (isset($arguments['sourceId']) === true && is_int((int) $arguments['sourceId']) === true) {
            $response['stackTrace'][] = "Found sourceId {$arguments['sourceId']} in arguments";
            $source = $this->sourceMapper->find((int) $arguments['sourceId']);
        } else {
            // @todo log and / or not default to just using the first source.
            $response['stackTrace'][] = "No sourceId in arguments, default to sourceId = 1";
            $source = $this->sourceMapper->find(1);
        }

        $response['stackTrace'][] = "Calling callService...";
        $callLog = $this->callService->call($source);

        $response['stackTrace'][] = "Created callLog with id: ".$callLog->getId();

        // Let's report back about what we have just done.
        return $response;

    }//end run()


}//end class
