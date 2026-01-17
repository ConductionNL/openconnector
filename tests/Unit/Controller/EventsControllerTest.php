<?php

declare(strict_types=1);

/**
 * EventsControllerTest
 * 
 * Unit tests for the EventsController
 *
 * @category   Test
 * @package    OCA\OpenConnector\Tests\Unit\Controller
 * @author     Conduction.nl <info@conduction.nl>
 * @copyright  Conduction.nl 2024
 * @license    EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version    1.0.0
 * @link       https://github.com/ConductionNL/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Controller;

use OCA\OpenConnector\Controller\EventsController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\EventService;
use OCA\OpenConnector\Db\Event;
use OCA\OpenConnector\Db\EventMapper;
use OCA\OpenConnector\Db\EventMessage;
use OCA\OpenConnector\Db\EventMessageMapper;
use OCA\OpenConnector\Db\EventSubscription;
use OCA\OpenConnector\Db\EventSubscriptionMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the EventsController
 *
 * This test class covers all functionality of the EventsController
 * including event listing, creation, updates, deletion, messages,
 * subscriptions, and event pulling.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class EventsControllerTest extends TestCase
{
    /**
     * The EventsController instance being tested
     *
     * @var EventsController
     */
    private EventsController $controller;

    /**
     * Mock request object
     *
     * @var MockObject|IRequest
     */
    private MockObject $request;

    /**
     * Mock app config
     *
     * @var MockObject|IAppConfig
     */
    private MockObject $config;

    /**
     * Mock event mapper
     *
     * @var MockObject|EventMapper
     */
    private MockObject $eventMapper;

    /**
     * Mock event service
     *
     * @var MockObject|EventService
     */
    private MockObject $eventService;

    /**
     * Mock event message mapper
     *
     * @var MockObject|EventMessageMapper
     */
    private MockObject $messageMapper;

    /**
     * Mock event subscription mapper
     *
     * @var MockObject|EventSubscriptionMapper
     */
    private MockObject $subscriptionMapper;

    /**
     * Set up test environment before each test
     *
     * This method initializes all mocks and the controller instance
     * for testing purposes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects for all dependencies
        $this->request = $this->createMock(IRequest::class);
        $this->config = $this->createMock(IAppConfig::class);
        $this->eventMapper = $this->createMock(EventMapper::class);
        $this->eventService = $this->createMock(EventService::class);
        $this->messageMapper = $this->createMock(EventMessageMapper::class);
        $this->subscriptionMapper = $this->createMock(EventSubscriptionMapper::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new EventsController(
            'openconnector',
            $this->request,
            $this->config,
            $this->eventMapper,
            $this->eventService,
            $this->messageMapper,
            $this->subscriptionMapper
        );
    }

    /**
     * Test successful page rendering
     *
     * This test verifies that the page() method returns a proper TemplateResponse.
     *
     * @return void
     */
    public function testPageSuccessful(): void
    {
        // Execute the method
        $response = $this->controller->page();

        // Assert response is a TemplateResponse
        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertEquals('index', $response->getTemplateName());
        $this->assertEquals([], $response->getParams());
    }

    /**
     * Test successful retrieval of all events
     *
     * This test verifies that the index() method returns correct event data
     * with search functionality.
     *
     * @return void
     */
    public function testIndexSuccessful(): void
    {
        // Setup mock request parameters
        $filters = ['search' => 'test', 'limit' => 10];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($filters);

        // Create mock services
        $objectService = $this->createMock(ObjectService::class);
        $searchService = $this->createMock(SearchService::class);

        // Mock search service methods
        $searchService->expects($this->once())
            ->method('createMySQLSearchParams')
            ->with($filters)
            ->willReturn(['search' => 'test']);

        $searchService->expects($this->once())
            ->method('createMySQLSearchConditions')
            ->with($filters, ['name', 'description'])
            ->willReturn(['conditions' => 'name LIKE %test%']);

        $searchService->expects($this->once())
            ->method('unsetSpecialQueryParams')
            ->with($filters)
            ->willReturn(['limit' => 10]);

        // Mock event mapper
        $expectedEvents = [
            new Event(),
            new Event()
        ];
        $this->eventMapper->expects($this->once())
            ->method('findAll')
            ->with(
                null, // limit
                null, // offset
                ['limit' => 10], // filters
                ['conditions' => 'name LIKE %test%'], // searchConditions
                ['search' => 'test'] // searchParams
            )
            ->willReturn($expectedEvents);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedEvents], $response->getData());
    }

    /**
     * Test successful retrieval of a single event
     *
     * This test verifies that the show() method returns correct event data
     * for a valid event ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $eventId = '123';
        $expectedEvent = new Event();
        $expectedEvent->setId((int) $eventId);
        $expectedEvent->setType('test.event');

        // Mock event mapper to return the expected event
        $this->eventMapper->expects($this->once())
            ->method('find')
            ->with((int) $eventId)
            ->willReturn($expectedEvent);

        // Execute the method
        $response = $this->controller->show($eventId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedEvent, $response->getData());
    }

    /**
     * Test event retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the event ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $eventId = '999';

        // Mock event mapper to throw DoesNotExistException
        $this->eventMapper->expects($this->once())
            ->method('find')
            ->with((int) $eventId)
            ->willThrowException(new DoesNotExistException('Event not found'));

        // Execute the method
        $response = $this->controller->show($eventId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful event creation
     *
     * This test verifies that the create() method creates a new event
     * and returns the created event data.
     *
     * @return void
     */
    public function testCreateSuccessful(): void
    {
        $eventData = [
            'type' => 'new.event',
            'source' => 'test.source',
            '_internal' => 'should_be_removed',
            'id' => '999' // should be removed
        ];

        $expectedEvent = new Event();
        $expectedEvent->setType('new.event');
        $expectedEvent->setSource('test.source');

        // Mock request to return event data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($eventData);

        // Mock event mapper to return the created event
        $this->eventMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['type' => 'new.event', 'source' => 'test.source'])
            ->willReturn($expectedEvent);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedEvent, $response->getData());
    }

    /**
     * Test successful event update
     *
     * This test verifies that the update() method updates an existing event
     * and returns the updated event data.
     *
     * @return void
     */
    public function testUpdateSuccessful(): void
    {
        $eventId = 123;
        $updateData = [
            'type' => 'updated.event',
            'source' => 'updated://source',
            '_internal' => 'should_be_removed',
            'id' => '999' // should be removed
        ];

        $updatedEvent = new Event();
        $updatedEvent->setId($eventId);
        $updatedEvent->setType('updated.event');
        $updatedEvent->setSource('updated://source');

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock event mapper to return updated event
        $this->eventMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($eventId, ['type' => 'updated.event', 'source' => 'updated://source'])
            ->willReturn($updatedEvent);

        // Execute the method
        $response = $this->controller->update($eventId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedEvent, $response->getData());
    }

    /**
     * Test successful event deletion
     *
     * This test verifies that the destroy() method deletes an event
     * and returns an empty response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $eventId = 123;
        $existingEvent = new Event();
        $existingEvent->setId($eventId);
        $existingEvent->setType('test.event');

        // Mock event mapper to return existing event and handle deletion
        $this->eventMapper->expects($this->once())
            ->method('find')
            ->with($eventId)
            ->willReturn($existingEvent);

        $this->eventMapper->expects($this->once())
            ->method('delete')
            ->with($existingEvent);

        // Execute the method
        $response = $this->controller->destroy($eventId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals([], $response->getData());
    }

    /**
     * Test successful retrieval of event messages
     *
     * This test verifies that the messages() method returns correct
     * event and message data.
     *
     * @return void
     */
    public function testMessagesSuccessful(): void
    {
        $eventId = 123;
        $expectedEvent = new Event();
        $expectedEvent->setId($eventId);
        $expectedEvent->setType('test.event');

        $expectedMessages = [
            new EventMessage(),
            new EventMessage()
        ];

        // Mock request to return pagination parameters
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['limit', 50], ['offset', 0])
            ->willReturnOnConsecutiveCalls(50, 0);

        // Mock event mapper to return the expected event
        $this->eventMapper->expects($this->once())
            ->method('find')
            ->with($eventId)
            ->willReturn($expectedEvent);

        // Mock message mapper to return messages
        $this->messageMapper->expects($this->once())
            ->method('findAll')
            ->with(50, 0, ['eventId' => $eventId])
            ->willReturn($expectedMessages);

        // Execute the method
        $response = $this->controller->messages($eventId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedEvent, $data['event']);
        $this->assertEquals($expectedMessages, $data['messages']);
    }

    /**
     * Test event messages with non-existent event
     *
     * This test verifies that the messages() method returns a 404 error
     * when the event ID does not exist.
     *
     * @return void
     */
    public function testMessagesWithNonExistentEvent(): void
    {
        $eventId = 999;

        // Mock event mapper to throw DoesNotExistException
        $this->eventMapper->expects($this->once())
            ->method('find')
            ->with($eventId)
            ->willThrowException(new DoesNotExistException('Event not found'));

        // Execute the method
        $response = $this->controller->messages($eventId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Event not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful subscription creation
     *
     * This test verifies that the subscribe() method creates a new subscription
     * and returns the created subscription data.
     *
     * @return void
     */
    public function testSubscribeSuccessful(): void
    {
        $subscriptionData = [
            'sink' => 'https://example.com/webhook',
            '_internal' => 'should_be_removed'
        ];

        $expectedSubscription = new EventSubscription();
        $expectedSubscription->setSink('https://example.com/webhook');

        // Mock request to return subscription data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($subscriptionData);

        // Mock subscription mapper to return the created subscription
        $this->subscriptionMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['sink' => 'https://example.com/webhook'])
            ->willReturn($expectedSubscription);

        // Execute the method
        $response = $this->controller->subscribe();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedSubscription, $response->getData());
    }

    /**
     * Test subscription creation with error
     *
     * This test verifies that the subscribe() method returns an error response
     * when subscription creation fails.
     *
     * @return void
     */
    public function testSubscribeWithError(): void
    {
        $subscriptionData = ['invalid' => 'data'];

        // Mock request to return subscription data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($subscriptionData);

        // Mock subscription mapper to throw exception
        $this->subscriptionMapper->expects($this->once())
            ->method('createFromArray')
            ->with($subscriptionData)
            ->willThrowException(new \Exception('Invalid data'));

        // Execute the method
        $response = $this->controller->subscribe();

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Invalid data'], $response->getData());
        $this->assertEquals(400, $response->getStatus());
    }

    /**
     * Test successful subscription update
     *
     * This test verifies that the updateSubscription() method updates an existing subscription
     * and returns the updated subscription data.
     *
     * @return void
     */
    public function testUpdateSubscriptionSuccessful(): void
    {
        $subscriptionId = 123;
        $updateData = [
            'sink' => 'https://updated.com/webhook',
            '_internal' => 'should_be_removed'
        ];

        $updatedSubscription = new EventSubscription();
        $updatedSubscription->setId($subscriptionId);
        $updatedSubscription->setSink('https://updated.com/webhook');

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock subscription mapper to return updated subscription
        $this->subscriptionMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($subscriptionId, ['sink' => 'https://updated.com/webhook'])
            ->willReturn($updatedSubscription);

        // Execute the method
        $response = $this->controller->updateSubscription($subscriptionId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedSubscription, $response->getData());
    }

    /**
     * Test subscription update with non-existent subscription
     *
     * This test verifies that the updateSubscription() method returns a 404 error
     * when the subscription ID does not exist.
     *
     * @return void
     */
    public function testUpdateSubscriptionWithNonExistentId(): void
    {
        $subscriptionId = 999;
        $updateData = ['url' => 'https://example.com/webhook'];

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock subscription mapper to throw DoesNotExistException
        $this->subscriptionMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($subscriptionId, $updateData)
            ->willThrowException(new DoesNotExistException('Subscription not found'));

        // Execute the method
        $response = $this->controller->updateSubscription($subscriptionId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Subscription not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful subscription deletion
     *
     * This test verifies that the unsubscribe() method deletes a subscription
     * and returns an empty response.
     *
     * @return void
     */
    public function testUnsubscribeSuccessful(): void
    {
        $subscriptionId = 123;
        $existingSubscription = new EventSubscription();
        $existingSubscription->setId($subscriptionId);

        // Mock subscription mapper to return existing subscription and handle deletion
        $this->subscriptionMapper->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willReturn($existingSubscription);

        $this->subscriptionMapper->expects($this->once())
            ->method('delete')
            ->with($existingSubscription);

        // Execute the method
        $response = $this->controller->unsubscribe($subscriptionId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals([], $response->getData());
    }

    /**
     * Test subscription deletion with non-existent subscription
     *
     * This test verifies that the unsubscribe() method returns a 404 error
     * when the subscription ID does not exist.
     *
     * @return void
     */
    public function testUnsubscribeWithNonExistentId(): void
    {
        $subscriptionId = 999;

        // Mock subscription mapper to throw DoesNotExistException
        $this->subscriptionMapper->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willThrowException(new DoesNotExistException('Subscription not found'));

        // Execute the method
        $response = $this->controller->unsubscribe($subscriptionId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Subscription not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful retrieval of all subscriptions
     *
     * This test verifies that the subscriptions() method returns correct
     * subscription data with pagination.
     *
     * @return void
     */
    public function testSubscriptionsSuccessful(): void
    {
        $filters = ['eventId' => 123, '_internal' => 'should_be_removed'];
        $expectedSubscriptions = [
            new EventSubscription(),
            new EventSubscription()
        ];

        // Mock request to return filters and pagination parameters
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($filters);

        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['limit', 50], ['offset', 0])
            ->willReturnOnConsecutiveCalls(50, 0);

        // Mock subscription mapper to return subscriptions
        $this->subscriptionMapper->expects($this->once())
            ->method('findAll')
            ->with(50, 0, ['eventId' => 123])
            ->willReturn($expectedSubscriptions);

        // Execute the method
        $response = $this->controller->subscriptions();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedSubscriptions], $response->getData());
    }

    /**
     * Test successful retrieval of subscription messages
     *
     * This test verifies that the subscriptionMessages() method returns correct
     * subscription and message data.
     *
     * @return void
     */
    public function testSubscriptionMessagesSuccessful(): void
    {
        $subscriptionId = 123;
        $expectedSubscription = new EventSubscription();
        $expectedSubscription->setId($subscriptionId);

        $expectedMessages = [
            new EventMessage(),
            new EventMessage()
        ];

        // Mock request to return pagination parameters
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['limit', 50], ['offset', 0])
            ->willReturnOnConsecutiveCalls(50, 0);

        // Mock subscription mapper to return the expected subscription
        $this->subscriptionMapper->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willReturn($expectedSubscription);

        // Mock message mapper to return messages
        $this->messageMapper->expects($this->once())
            ->method('findAll')
            ->with(50, 0, ['subscriptionId' => $subscriptionId])
            ->willReturn($expectedMessages);

        // Execute the method
        $response = $this->controller->subscriptionMessages($subscriptionId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertEquals($expectedSubscription, $data['subscription']);
        $this->assertEquals($expectedMessages, $data['messages']);
    }

    /**
     * Test subscription messages with non-existent subscription
     *
     * This test verifies that the subscriptionMessages() method returns a 404 error
     * when the subscription ID does not exist.
     *
     * @return void
     */
    public function testSubscriptionMessagesWithNonExistentId(): void
    {
        $subscriptionId = 999;

        // Mock subscription mapper to throw DoesNotExistException
        $this->subscriptionMapper->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willThrowException(new DoesNotExistException('Subscription not found'));

        // Execute the method
        $response = $this->controller->subscriptionMessages($subscriptionId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Subscription not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful event pulling
     *
     * This test verifies that the pull() method returns correct
     * event data for pull-based subscriptions.
     *
     * @return void
     */
    public function testPullSuccessful(): void
    {
        $subscriptionId = 123;
        $expectedSubscription = new EventSubscription();
        $expectedSubscription->setId($subscriptionId);
        $expectedSubscription->setStyle('pull');

        $expectedResult = [
            'messages' => [new EventMessage()],
            'cursor' => 'next_cursor'
        ];

        // Mock request to return pagination parameters
        $this->request->expects($this->exactly(2))
            ->method('getParam')
            ->withConsecutive(['limit', 100], ['cursor'])
            ->willReturnOnConsecutiveCalls(100, 'current_cursor');

        // Mock subscription mapper to return the expected subscription
        $this->subscriptionMapper->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willReturn($expectedSubscription);

        // Mock event service to return pull result
        $this->eventService->expects($this->once())
            ->method('pullEvents')
            ->with($expectedSubscription, 100, 'current_cursor')
            ->willReturn($expectedResult);

        // Execute the method
        $response = $this->controller->pull($subscriptionId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedResult, $response->getData());
    }

    /**
     * Test event pulling with non-pull subscription
     *
     * This test verifies that the pull() method returns an error response
     * when the subscription is not pull-based.
     *
     * @return void
     */
    public function testPullWithNonPullSubscription(): void
    {
        $subscriptionId = 123;
        $expectedSubscription = new EventSubscription();
        $expectedSubscription->setId($subscriptionId);
        $expectedSubscription->setStyle('push');

        // Mock subscription mapper to return the expected subscription
        $this->subscriptionMapper->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willReturn($expectedSubscription);

        // Execute the method
        $response = $this->controller->pull($subscriptionId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Subscription is not pull-based'], $response->getData());
        $this->assertEquals(400, $response->getStatus());
    }

    /**
     * Test event pulling with non-existent subscription
     *
     * This test verifies that the pull() method returns a 404 error
     * when the subscription ID does not exist.
     *
     * @return void
     */
    public function testPullWithNonExistentId(): void
    {
        $subscriptionId = 999;

        // Mock subscription mapper to throw DoesNotExistException
        $this->subscriptionMapper->expects($this->once())
            ->method('find')
            ->with($subscriptionId)
            ->willThrowException(new DoesNotExistException('Subscription not found'));

        // Execute the method
        $response = $this->controller->pull($subscriptionId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Subscription not found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test index method with empty filters
     *
     * This test verifies that the index() method handles empty filters correctly.
     *
     * @return void
     */
    public function testIndexWithEmptyFilters(): void
    {
        // Setup mock request parameters with empty filters
        $filters = [];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($filters);

        // Create mock services
        $objectService = $this->createMock(ObjectService::class);
        $searchService = $this->createMock(SearchService::class);

        // Mock search service methods
        $searchService->expects($this->once())
            ->method('createMySQLSearchParams')
            ->with($filters)
            ->willReturn([]);

        $searchService->expects($this->once())
            ->method('createMySQLSearchConditions')
            ->with($filters, ['name', 'description'])
            ->willReturn([]);

        $searchService->expects($this->once())
            ->method('unsetSpecialQueryParams')
            ->with($filters)
            ->willReturn([]);

        // Mock event mapper
        $expectedEvents = [];
        $this->eventMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [], [], [])
            ->willReturn($expectedEvents);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedEvents], $response->getData());
    }
}
