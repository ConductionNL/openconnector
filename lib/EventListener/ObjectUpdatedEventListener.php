<?php

namespace OCA\OpenConnector\EventListener;

use OCA\OpenConnector\Service\SynchronizationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use Psr\Log\LoggerInterface;

class ObjectUpdatedEventListener implements IEventListener
{

	public function __construct(
		private readonly SynchronizationService $synchronizationService,
        private readonly LoggerInterface $logger,
	)
	{
	}

	/**
     * @inheritDoc
     */
    public function handle(Event $event): void
    {
        if ($event instanceof ObjectUpdatedEvent === false) {
            return;
        }

        if (method_exists($event, 'getNewObject') === false) {
            return;
        }


        $object = $event->getNewObject();
        if ($object === null || $object->getRegister() === null || $object->getSchema() === null && $object->getNewObject() !== null) {
            return;
        }

        $synchronizations = $this->synchronizationService->findAllBySourceId(register: $object->getRegister(), schema: $object->getSchema());
        foreach ($synchronizations as $synchronization) {
            try {
                $this->synchronizationService->synchronize(synchronization: $synchronization, force: true, object: $object->jsonSerialize(), mutationType: 'update');
            } catch (\Exception $e) {
                $this->logger->error('Failed to process object event: ' . $e->getMessage() . ' for synchronization ' . $synchronization->getId(), [
                    'exception' => $e,
                    'event' => get_class($event)
                ]);
            }
        }
    }
}
