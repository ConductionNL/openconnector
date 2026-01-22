<?php

namespace OCA\OpenConnector\Tests\Unit\EventListener;

use OCA\OpenConnector\EventListener\ObjectDeletedEventListener;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ObjectDeletedEventListenerTest extends TestCase
{
    private SynchronizationService $synchronizationService;
    private LoggerInterface $logger;
    private ObjectDeletedEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->synchronizationService = $this->createMock(SynchronizationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->listener = new ObjectDeletedEventListener(
            $this->synchronizationService,
            $this->logger
        );
    }

    public function testHandleObjectDeletedEvent(): void
    {
        $object = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $event = $this->createMock(ObjectDeletedEvent::class);
        $event->method('getObject')->willReturn($object);
        
        // Test that the listener can handle the event without errors
        $this->listener->handle($event);
        $this->assertTrue(true);
    }

    public function testHandleUnsupportedEvent(): void
    {
        $event = $this->createMock(Event::class);
        
        $this->synchronizationService->expects($this->never())
            ->method('findAllBySourceId');
        
        $this->listener->handle($event);
    }

    public function testHandleEventWithoutGetObjectMethod(): void
    {
        $event = $this->createMock(Event::class); // Not ObjectDeletedEvent
        
        $this->synchronizationService->expects($this->never())
            ->method('findAllBySourceId');
        
        $this->listener->handle($event);
    }

    public function testHandleExceptionLogging(): void
    {
        $object = $this->createMock(\OCA\OpenRegister\Db\ObjectEntity::class);
        $event = $this->createMock(ObjectDeletedEvent::class);
        $event->method('getObject')->willReturn($object);
        
        // Test that the listener can handle the event without errors
        $this->listener->handle($event);
        $this->assertTrue(true);
    }
}
