<?php
/**
 * OpenConnector Cloud Event Listener
 *
 * This file contains the event listener for forwarding object events to the
 * EventService in the OpenConnector application.
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

use OCA\OpenConnector\Service\EventService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * Listener that forwards object changes to the EventService
 */
class CloudEventListener implements IEventListener
{


    /**
     * Constructor for CloudEventListener
     *
     * @param EventService    $eventService Service for managing CloudEvents
     * @param LoggerInterface $logger       Logger instance
     *
     * @return void
     */
    public function __construct(
        private readonly EventService $eventService,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()


    /**
     * Handle incoming events by forwarding them to the EventService
     *
     * @param Event $event The incoming event
     *
     * @return void
     */
    public function handle(Event $event): void
    {
        if ($event instanceof ObjectCreatedEvent === false
            && $event instanceof ObjectUpdatedEvent === false
            && $event instanceof ObjectDeletedEvent === false
        ) {
            return;
        }

        try {
            if ($event instanceof ObjectCreatedEvent) {
                $this->eventService->handleObjectCreated($event->getObject());
            } else if ($event instanceof ObjectUpdatedEvent) {
                $this->eventService->handleObjectUpdated($event->getOldObject(), $event->getNewObject());
            } else {
                $this->eventService->handleObjectDeleted($event->getObject());
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Failed to process object event: '.$e->getMessage(),
                [
                    'exception' => $e,
                    'event'     => get_class($event),
                ]
            );
        }

    }//end handle()


}//end class
