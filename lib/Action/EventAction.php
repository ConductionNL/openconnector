<?php
/**
 * OpenConnector Event Action
 *
 * This file contains the action class for handling event-related operations
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
 * This class is used to run event-related action tasks for the OpenConnector app.
 *
 * @package OCA\OpenConnector\Action
 */
class EventAction
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
     * Constructor for the EventAction class
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
        $this->callService = $callService;

    }//end __construct()


    /**
     * Runs the event action with the given arguments
     *
     * @param array $argument Optional arguments for the action
     *
     * @return array Returns an array of results from the action
     *
     * @todo: make this a bit more generic
     */
    public function run(array $argument=[]): array
    {
        // @todo: implement this
        // Let's report back about what we have just done
        return [];

    }//end run()


}//end class
