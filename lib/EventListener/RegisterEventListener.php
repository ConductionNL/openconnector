<?php

namespace OCA\OpenConnector\EventListener;

use OCA\OpenConnector\Service\SynchronizationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use Psr\Log\LoggerInterface;

/**
 * Listener that forwards object changes to the SynchronizationService
 */
class RegisterEventListener implements IEventListener
{
    /**
     * @param SynchronizationService $synchronizationService Service for synchronizing
     * @param LoggerInterface $logger Logger instance
     */
    public function __construct(
        private readonly SynchronizationService $synchronizationService,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Handle incoming events by forwarding them to the EventService
     *
     * @param Event $event The incoming event
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

        if (method_exists($event, 'getObject') === false) {
            return;
        }


        $object = $event->getObject();
        if ($object === null || $object->getRegister() === null || $object->getSchema() === null) {
            return;
        }

        $synchronizations = $this->synchronizationService->findAllBySourceId(register: $object->getRegister(), schema: $object->getSchema());
        foreach ($synchronizations as $synchronization) {
            try {
                if ($event instanceof ObjectCreatedEvent) {
                    $this->synchronizationService->synchronize($synchronization, false, $object);
                } elseif ($event instanceof ObjectUpdatedEvent) {
                    $this->synchronizationService->synchronize($synchronization, false, $object);
                } elseif ($event instanceof ObjectDeletedEvent) {
                    $this->synchronizationService->synchronize($synchronization, false, $object);
                }
            } catch (\Exception $e) {
                $this->logger->error('Failed to process object event: ' . $e->getMessage() . ' for synchronization ' . $synchronization->getId(), [
                    'exception' => $e,
                    'event' => get_class($event)
                ]);
            }
        }
    }
}
