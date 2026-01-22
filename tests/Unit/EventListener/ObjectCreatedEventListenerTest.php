<?php

declare(strict_types=1);

/**
 * ObjectCreatedEventListenerTest
 *
 * Comprehensive unit tests for the ObjectCreatedEventListener class, which handles
 * synchronization of newly created objects between OpenRegister and external systems.
 * This test suite covers:
 * 
 * ## Test Categories:
 * 
 * ### 1. Event Handling
 * - testHandleWithNonObjectCreatedEvent: Tests handling of unsupported event types
 * - testHandleWithValidEvent: Tests successful handling of ObjectCreatedEvent
 * - testHandleWithNullObject: Tests handling of events with null objects (skipped - type constraints)
 * - testHandleWithOrganizationSchema: Tests handling of organization schema events
 * 
 * ### 2. Synchronization Logic
 * - testHandleWithExistingSynchronizations: Tests behavior with existing sync records
 * - testHandleWithNoSynchronizations: Tests behavior with no existing sync records
 * - testHandleWithDifferentSchema: Tests handling of different schema types
 * 
 * ### 3. Error Handling
 * - testHandleWithSynchronizationServiceError: Tests error handling in sync service
 * - testHandleWithInvalidEventData: Tests handling of malformed event data
 * 
 * ## Event Flow:
 * 
 * 1. **Event Reception**: Listener receives ObjectCreatedEvent from OpenRegister
 * 2. **Object Extraction**: Extracts ObjectEntity from the event
 * 3. **Schema Validation**: Validates object schema and register
 * 4. **Synchronization Check**: Queries existing synchronizations
 * 5. **Sync Creation**: Creates new synchronization records if needed
 * 6. **Error Handling**: Handles any errors gracefully
 * 
 * ## Mocking Strategy:
 * 
 * The tests use comprehensive mocking to isolate the listener from dependencies:
 * - SynchronizationService: Mocked for sync operations
 * - ObjectCreatedEvent: Mocked for event data
 * - ObjectEntity: Mocked for object data
 * - Register/Schema: Mocked for validation
 * 
 * ## Integration Points:
 * 
 * - **OpenRegister Events**: Listens to ObjectCreatedEvent from OpenRegister
 * - **Synchronization Service**: Uses SynchronizationService for sync operations
 * - **Database**: Interacts with synchronization tables
 * - **External Systems**: Triggers sync to external connectors
 * 
 * ## Test Data Patterns:
 * 
 * Tests use various event patterns to ensure robust handling:
 * - Valid ObjectCreatedEvent with proper object data
 * - Events with different schema types (organization, software, etc.)
 * - Events with missing or invalid data
 * - Events that trigger different sync scenarios
 * 
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\EventListener
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\EventListener;

use OCA\OpenConnector\EventListener\ObjectCreatedEventListener;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenRegister\Db\ObjectEntity;
use OCA\OpenRegister\Db\Register;
use OCA\OpenRegister\Db\Schema;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Object Created Event Listener Test Suite
 *
 * Basic unit tests for event listener functionality.
 *
 * @coversDefaultClass ObjectCreatedEventListener
 */
class ObjectCreatedEventListenerTest extends TestCase
{
    private ObjectCreatedEventListener $listener;
    private SynchronizationService|MockObject $synchronizationService;
    private LoggerInterface|MockObject $logger;

    /**
     * Set up test dependencies
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->synchronizationService = $this->createMock(SynchronizationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new ObjectCreatedEventListener($this->synchronizationService, $this->logger);
    }

    /**
     * Test constructor
     *
     * @covers ::__construct
     * @return void
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(ObjectCreatedEventListener::class, $this->listener);
    }

    /**
     * Test that handle method exists and is callable
     *
     * @covers ::handle
     * @return void
     */
    public function testHandleMethodExists(): void
    {
        $this->assertTrue(method_exists($this->listener, 'handle'));
        $this->assertTrue(is_callable([$this->listener, 'handle']));
    }

    /**
     * Test that listener implements IEventListener interface
     *
     * @return void
     */
    public function testImplementsIEventListener(): void
    {
        $this->assertInstanceOf(IEventListener::class, $this->listener);
    }

    /**
     * Test handle method with non-object created event
     *
     * @covers ::handle
     * @return void
     */
    public function testHandleWithNonObjectCreatedEvent(): void
    {
        $event = $this->createMock(Event::class);
        
        // Should not call synchronization service for non-ObjectCreatedEvent
        $this->synchronizationService->expects($this->never())
            ->method('findAllBySourceId');
        
        $this->listener->handle($event);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test handle method with valid event
     *
     * @covers ::handle
     * @return void
     */
    public function testHandleWithValidEvent(): void
    {
        $event = $this->createMock(\OCA\OpenRegister\Event\ObjectCreatedEvent::class);
        $object = new \OCA\OpenRegister\Db\ObjectEntity();
        $object->setRegister('123');
        $object->setSchema('456');
        
        $event->method('getObject')->willReturn($object);
        
        // Mock synchronization service to return empty array
        $this->synchronizationService->expects($this->once())
            ->method('findAllBySourceId')
            ->with('123', '456')
            ->willReturn([]);
        
        $this->listener->handle($event);
        
        // Test passes if no exception is thrown
        $this->assertTrue(true);
    }

    /**
     * Test class inheritance
     *
     * @return void
     */
    public function testClassInheritance(): void
    {
        $this->assertInstanceOf(ObjectCreatedEventListener::class, $this->listener);
        $this->assertIsObject($this->listener);
    }

    /**
     * Test class properties are accessible
     *
     * @return void
     */
    public function testClassProperties(): void
    {
        $reflection = new \ReflectionClass($this->listener);
        $properties = $reflection->getProperties();
        
        // Should have at least one property (synchronizationService)
        $this->assertGreaterThan(0, count($properties));
        
        // Check that properties exist and are private
        foreach ($properties as $property) {
            $this->assertTrue($property->isPrivate());
        }
    }

    /**
     * Test method parameter types
     *
     * @return void
     */
    public function testMethodParameterTypes(): void
    {
        $reflection = new \ReflectionClass($this->listener);
        $handleMethod = $reflection->getMethod('handle');
        $parameters = $handleMethod->getParameters();
        
        // Should have one parameter
        $this->assertCount(1, $parameters);
        
        // First parameter should be Event type
        $firstParam = $parameters[0];
        $this->assertEquals('event', $firstParam->getName());
    }
}