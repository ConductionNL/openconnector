<?php
/**
 * OpenConnector Object Created Event Listener
 *
 * This file contains the event listener for handling object creation events
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
 * Event listener for object creation events
 */
class ObjectCreatedEventListener implements IEventListener
{


    /**
     * Constructor for the ObjectCreatedEventListener
     *
     * @param SynchronizationService $synchronizationService Service for synchronization operations
     *
     * @return void
     */
    public function __construct(
        private readonly SynchronizationService $synchronizationService
    ) {

    }//end __construct()


    /**
     * Handles an object creation event
     *
     * This method processes object creation events and triggers the appropriate
     * synchronization if the event is of the correct type.
     *
     * @param Event $event The event to handle
     *
     * @return void
     */
    public function handle(Event $event): void
    {
        if ($event instanceof ObjectCreatedEvent === false) {
            return;
        }

        $object = $event->getObject();
        $this->synchronizationService->synchronizeToTarget($object);

    }//end handle()


}//end class
