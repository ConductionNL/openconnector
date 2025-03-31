<?php
/**
 * OpenConnector Object Deleted Event Listener
 *
 * This file contains the event listener for handling object deletion events
 * in the OpenConnector application.
 *
 * @category  EventListener
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\EventListener;

use OCA\OpenConnector\Service\SynchronizationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCA\OpenConnector\Db\SynchronizationContractMapper;

/**
 * Event listener for object deletion events
 */
class ObjectDeletedEventListener implements IEventListener
{


    /**
     * Constructor for the ObjectDeletedEventListener
     *
     * @param SynchronizationService        $synchronizationService        Service for synchronization operations
     * @param SynchronizationContractMapper $synchronizationContractMapper Mapper for synchronization contracts
     *
     * @return void
     */
    public function __construct(
        private readonly SynchronizationService $synchronizationService,
        private readonly SynchronizationContractMapper $synchronizationContractMapper,
    ) {

    }//end __construct()


    /**
     * Handles an object deletion event
     *
     * This method processes object deletion events and triggers the appropriate
     * synchronization if the event is of the correct type.
     *
     * @param Event $event The event to handle
     *
     * @return void
     */
    public function handle(Event $event): void
    {
        if ($event instanceof ObjectDeletedEvent === false) {
            return;
        }

        $object = $event->getObject();

        $contracts = $this->synchronizationContractMapper->handleObjectRemoval($object->getUuid());

        foreach ($contracts as $contract) {
            $this->synchronizationService->synchronizeToTarget($object, $contract);
        }

    }//end handle()


}//end class
