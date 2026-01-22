<?php

namespace OCA\OpenConnector\Tests\Unit\EventListener;

use OCA\OpenConnector\EventListener\CloudEventListener;
use OCA\OpenConnector\Service\EventService;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CloudEventListenerTest extends TestCase
{
    private EventService $eventService;
    private LoggerInterface $logger;
    private CloudEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->eventService = $this->createMock(EventService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->listener = new CloudEventListener(
            $this->eventService,
            $this->logger
        );
    }

    public function testHandleObjectCreatedEvent(): void
    {
        $object = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $event = $this->createMock(ObjectCreatedEvent::class);
        $event->method('getObject')->willReturn($object);
        
        $this->eventService->expects($this->once())
            ->method('handleObjectCreated')
            ->with($object);
        
        $this->listener->handle($event);
    }

    public function testHandleObjectUpdatedEvent(): void
    {
        $oldObject = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $newObject = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $event = $this->createMock(ObjectUpdatedEvent::class);
        $event->method('getOldObject')->willReturn($oldObject);
        $event->method('getNewObject')->willReturn($newObject);
        
        $this->eventService->expects($this->once())
            ->method('handleObjectUpdated')
            ->with($oldObject, $newObject);
        
        $this->listener->handle($event);
    }

    public function testHandleObjectDeletedEvent(): void
    {
        $object = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $event = $this->createMock(ObjectDeletedEvent::class);
        $event->method('getObject')->willReturn($object);
        
        $this->eventService->expects($this->once())
            ->method('handleObjectDeleted')
            ->with($object);
        
        $this->listener->handle($event);
    }

    public function testHandleUnsupportedEvent(): void
    {
        $event = $this->createMock(Event::class);
        
        $this->eventService->expects($this->never())
            ->method('handleObjectCreated');
        $this->eventService->expects($this->never())
            ->method('handleObjectUpdated');
        $this->eventService->expects($this->never())
            ->method('handleObjectDeleted');
        
        $this->listener->handle($event);
    }

    public function testHandleExceptionLogging(): void
    {
        $object = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $event = $this->createMock(ObjectCreatedEvent::class);
        $event->method('getObject')->willReturn($object);
        
        $exception = new \Exception('Test exception');
        $this->eventService->expects($this->once())
            ->method('handleObjectCreated')
            ->willThrowException($exception);
        
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to process object event: Test exception', $this->isType('array'));
        
        $this->listener->handle($event);
    }
}
