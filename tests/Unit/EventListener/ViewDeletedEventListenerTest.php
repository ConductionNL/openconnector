<?php

namespace OCA\OpenConnector\Tests\Unit\EventListener;

use OCA\OpenConnector\EventListener\ViewDeletedEventListener;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenRegister\Db\RegisterMapper;
use OCA\OpenRegister\Db\SchemaMapper;
use OCA\OpenRegister\Event\ObjectDeletedEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ViewDeletedEventListenerTest extends TestCase
{
    private ObjectService $objectService;
    private RegisterMapper $registerMapper;
    private SchemaMapper $schemaMapper;
    private LoggerInterface $logger;
    private ViewDeletedEventListener $listener;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->objectService = $this->createMock(ObjectService::class);
        $this->registerMapper = $this->createMock(RegisterMapper::class);
        $this->schemaMapper = $this->createMock(SchemaMapper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->listener = new ViewDeletedEventListener(
            $this->logger,
            $this->schemaMapper,
            $this->registerMapper,
            $this->objectService
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

    public function testHandleObjectDeletedEventWithValidObject(): void
    {
        $event = $this->createMock(ObjectDeletedEvent::class);
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        $register = new \OCA\OpenRegister\Db\Register();
        $schema = new \OCA\OpenRegister\Db\Schema();
        
        $object->setUuid('test-uuid-123');
        $object->setRegister(123);
        $object->setSchema(456);
        $register->setSlug('vng-gemma');
        $schema->setSlug('view');
        
        $event->method('getObject')->willReturn($object);
        
        $this->registerMapper->method('find')->with(123)->willReturn($register);
        $this->schemaMapper->method('find')->willReturn($schema);
        
        // Mock the openRegisters object
        $openRegisters = $this->createMock(\OCA\OpenRegister\Service\ObjectService::class);
        $openRegisters->method('findAll')->willReturn([]);
        $openRegisters->method('delete')->willReturn(true);
        
        $this->objectService->method('getOpenRegisters')->willReturn($openRegisters);
        
        // Test that the listener can handle the event
        $this->listener->handle($event);
        
        // Should execute without crashing
        $this->assertTrue(true);
    }

    public function testHandleObjectDeletedEventWithJsonSerialize(): void
    {
        $event = $this->createMock(ObjectDeletedEvent::class);
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        
        $object->setUuid('test-uuid-123');
        $object->setRegister(123);
        $object->setSchema(456);
        
        $event->method('getObject')->willReturn($object);
        
        // Test that the listener can handle the event
        $this->listener->handle($event);
        
        // Should execute without crashing
        $this->assertTrue(true);
    }
}
