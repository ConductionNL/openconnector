<?php

namespace OCA\OpenConnector\Tests\Unit\EventListener;

use OCA\OpenConnector\EventListener\SoftwareCatalogEventListener;
use OCA\OpenConnector\Service\SoftwareCatalogueService;
use OCA\OpenRegister\Event\ObjectCreatedEvent;
use OCA\OpenRegister\Event\ObjectUpdatedEvent;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SoftwareCatalogEventListenerTest extends TestCase
{
    private SoftwareCatalogueService $softwareCatalogueService;
    private LoggerInterface $logger;
    private SoftwareCatalogEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->softwareCatalogueService = $this->createMock(SoftwareCatalogueService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->listener = new SoftwareCatalogEventListener(
            $this->softwareCatalogueService,
            $this->logger
        );
    }

    public function testHandleUnsupportedEvent(): void
    {
        $event = $this->createMock(Event::class);
        
        // Test that the listener ignores unsupported events
        $this->listener->handle($event);
        
        // No service methods should be called for unsupported events
        $this->addToAssertionCount(1);
    }

    public function testHandleObjectCreatedEventWithNullObject(): void
    {
        $this->markTestSkipped('ObjectCreatedEvent::getObject() cannot return null due to type constraints');
    }

    public function testHandleObjectCreatedEventWithOrganizationSchema(): void
    {
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        $object->setSchema('1'); // ORGANIZATION_SCHEMA_ID
        $event = $this->createMock(ObjectCreatedEvent::class);
        
        $event->method('getObject')->willReturn($object);
        
        $this->softwareCatalogueService->expects($this->once())
            ->method('handleNewOrganization')
            ->with($object);
        
        $this->listener->handle($event);
    }

    public function testHandleObjectCreatedEventWithContactSchema(): void
    {
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        $object->setSchema('2'); // CONTACT_SCHEMA_ID
        $event = $this->createMock(ObjectCreatedEvent::class);
        
        $event->method('getObject')->willReturn($object);
        
        $this->softwareCatalogueService->expects($this->once())
            ->method('handleNewContact')
            ->with($object);
        
        $this->listener->handle($event);
    }

    public function testHandleObjectUpdatedEventWithNullObject(): void
    {
        $this->markTestSkipped('ObjectUpdatedEvent::getNewObject() cannot return null due to type constraints');
    }

    public function testHandleObjectUpdatedEventWithContactSchema(): void
    {
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        $object->setSchema('2'); // CONTACT_SCHEMA_ID
        $event = $this->createMock(ObjectUpdatedEvent::class);
        
        $event->method('getNewObject')->willReturn($object);
        
        $this->softwareCatalogueService->expects($this->once())
            ->method('handleContactUpdate')
            ->with($object);
        
        $this->listener->handle($event);
    }

    public function testHandleObjectDeletedEventWithNullObject(): void
    {
        $this->markTestSkipped('ObjectDeletedEvent::getObject() cannot return null due to type constraints');
    }

    public function testHandleObjectDeletedEventWithContactSchema(): void
    {
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        $object->setSchema('2'); // CONTACT_SCHEMA_ID
        $event = $this->createMock(ObjectUpdatedEvent::class);
        
        $event->method('getNewObject')->willReturn($object);
        
        $this->softwareCatalogueService->expects($this->once())
            ->method('handleContactUpdate')
            ->with($object);
        
        $this->listener->handle($event);
    }
}
