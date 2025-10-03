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
        $register = $object->getRegister();
        $schema = $object->getSchema();
        if ($object === null || $register === null || $schema === null) {
            return;
        }

        $synchronizations = $this->synchronizationService->findAllBySourceId(register: $register, schema: $schema);
        foreach ($synchronizations as $synchronization) {
            try {
                $objectArray = $object->jsonSerialize();
                $this->synchronizationService->synchronize(synchronization: $synchronization, force: true, object: $objectArray, mutationType: 'update');
            } catch (\Exception $e) {
                $this->logger->error('Failed to process object event: ' . $e->getMessage() . ' for synchronization ' . $synchronization->getId(), [
                    'exception' => $e,
                    'event' => get_class($event)
                ]);
            }
        }
    }
}
