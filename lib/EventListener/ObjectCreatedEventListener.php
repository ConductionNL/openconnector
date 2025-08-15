<?php

namespace OCA\OpenConnector\EventListener;

use OCA\OpenConnector\Service\SynchronizationService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use Psr\Log\LoggerInterface;

class ObjectCreatedEventListener implements IEventListener
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
        if ($event instanceof ObjectCreatedEvent === false) {
            return;
        }

        if (method_exists($event, 'getObject') === false) {
            return;
        }


        $object = $event->getObject();
        if ($object === null || $object->getRegister() === null || $object->getSchema() === null && $object->getObject() !== null) {
            return;
        }

        $synchronizations = $this->synchronizationService->findSynchronizationsToRun(object: $object);

        foreach ($synchronizations as $synchronization) {
            try {
                $array = $object->jsonSerialize();
                $this->synchronizationService->synchronize(synchronization: $synchronization, force: true, object: $array, mutationType: 'create');
            } catch (\Exception $e) {
                $this->logger->error('Failed to process object event: ' . $e->getMessage() . ' for synchronization ' . $synchronization->getId(), [
                    'exception' => $e,
                    'event' => get_class($event)
                ]);
            }
        }
    }
}
