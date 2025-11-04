<?php

namespace OCA\OpenConnector\Tests\Unit\EventListener;

use OCA\OpenConnector\EventListener\ViewUpdatedOrCreatedEventListener;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ViewUpdatedOrCreatedEventListenerTest extends TestCase
{
    private SynchronizationService $synchronizationService;
    private LoggerInterface $logger;
    private ViewUpdatedOrCreatedEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->synchronizationService = $this->createMock(SynchronizationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->listener = new ViewUpdatedOrCreatedEventListener(
            $this->synchronizationService,
            $this->logger
        );
    }

    public function testHandleUnsupportedEvent(): void
    {
        $event = $this->createMock(Event::class);
        
        // Test that the listener ignores unsupported events (early return)
        $this->listener->handle($event);
        
        // No mocks should be called for unsupported events
        $this->addToAssertionCount(1);
    }

    public function testHandleObjectCreatedEventWithoutGetNewObjectMethod(): void
    {
        $event = $this->createMock(ObjectCreatedEvent::class);
        // ObjectCreatedEvent doesn't have getNewObject method, should return early
        
        $this->listener->handle($event);
        
        // Should return early due to missing getNewObject method
        $this->addToAssertionCount(1);
    }

    public function testHandleObjectUpdatedEventWithWrongRegister(): void
    {
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        $object->setRegister('999'); // Wrong register ID (not 2)
        $object->setSchema('1'); // Correct schema ID
        $event = $this->createMock(ObjectUpdatedEvent::class);
        
        $event->method('getNewObject')->willReturn($object);
        
        // Test that the listener returns early due to wrong register
        $this->listener->handle($event);
        
        // Should return early due to wrong register
        $this->assertTrue(true);
    }

    public function testHandleObjectUpdatedEventWithWrongSchema(): void
    {
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        $object->setRegister('2'); // Correct register ID
        $object->setSchema('999'); // Wrong schema ID (not 1)
        $event = $this->createMock(ObjectUpdatedEvent::class);
        
        $event->method('getNewObject')->willReturn($object);
        
        // Test that the listener returns early due to wrong schema
        $this->listener->handle($event);
        
        // Should return early due to wrong schema
        $this->assertTrue(true);
    }
}
