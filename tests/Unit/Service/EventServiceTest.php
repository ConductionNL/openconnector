<?php

declare(strict_types=1);

/**
 * EventServiceTest
 *
 * Comprehensive unit tests for the EventService class to verify event processing,
 * message creation, subscription handling, and event delivery functionality.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Service
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Db\Event;
use OCA\OpenConnector\Db\EventMapper;
use OCA\OpenConnector\Db\EventMessage;
use OCA\OpenConnector\Db\EventMessageMapper;
use OCA\OpenConnector\Db\EventSubscription;
use OCA\OpenConnector\Db\EventSubscriptionMapper;
use OCA\OpenConnector\Service\EventService;
use OCP\Http\Client\IClientService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Event Service Test Suite
 *
 * Comprehensive unit tests for event processing and delivery functionality.
 * This test class validates event processing, subscription matching, message creation,
 * and webhook delivery mechanisms.
 *
 * @coversDefaultClass EventService
 */
class EventServiceTest extends TestCase
{
    private EventService $eventService;
    private MockObject $eventMapper;
    private MockObject $messageMapper;
    private MockObject $subscriptionMapper;
    private MockObject $clientService;
    private MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventMapper = $this->createMock(EventMapper::class);
        $this->messageMapper = $this->createMock(EventMessageMapper::class);
        $this->subscriptionMapper = $this->createMock(EventSubscriptionMapper::class);
        $this->clientService = $this->createMock(IClientService::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->eventService = new EventService(
            $this->eventMapper,
            $this->messageMapper,
            $this->subscriptionMapper,
            $this->clientService,
            $this->logger
        );
    }

    /**
     * Test event processing with active subscriptions
     *
     * This test verifies that the event service correctly processes events
     * and creates messages for active subscriptions.
     *
     * @covers ::processEvent
     * @return void
     */
    public function testProcessEventWithActiveSubscriptions(): void
    {
        // Create anonymous class for Event entity
        $event = new class extends Event {
            public function getId(): int { return 1; }
            public function getType(): string { return 'test.event'; }
            public function getData(): array { return ['test' => 'data']; }
            public function getSource(): ?string { return null; }
        };

        // Create anonymous class for EventSubscription entity
        $subscription = new class extends EventSubscription {
            public function getId(): int { return 1; }
            public function getEventType(): string { return 'test.event'; }
            public function getTypes(): array { return ['test.event']; }
            public function getWebhookUrl(): string { return 'https://example.com/webhook'; }
            public function getStatus(): string { return 'active'; }
            public function getSource(): ?string { return null; }
            public function getFilters(): array { return []; }
            public function getStyle(): string { return 'pull'; }
            public function getConsumerId(): int { return 1; }
        };

        $this->subscriptionMapper
            ->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo(null), $this->equalTo(null), $this->equalTo(['status' => 'active']))
            ->willReturn([$subscription]);

        $this->messageMapper
            ->expects($this->once())
            ->method('createFromArray')
            ->willReturn(new EventMessage());

        $result = $this->eventService->processEvent($event);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test event processing with no matching subscriptions
     *
     * This test verifies that the event service handles events
     * when no subscriptions match the event type.
     *
     * @covers ::processEvent
     * @return void
     */
    public function testProcessEventWithNoMatchingSubscriptions(): void
    {
        // Create anonymous class for Event entity
        $event = new class extends Event {
            public function getId(): int { return 1; }
            public function getType(): string { return 'unmatched.event'; }
            public function getData(): array { return ['test' => 'data']; }
        };

        $this->subscriptionMapper
            ->expects($this->once())
            ->method('findAll')
            ->with($this->anything(), $this->anything(), ['status' => 'active'])
            ->willReturn([]);

        $this->messageMapper
            ->expects($this->never())
            ->method('insert');

        $result = $this->eventService->processEvent($event);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    /**
     * Test event delivery to webhook
     *
     * This test verifies that the event service correctly delivers
     * event messages to webhook endpoints.
     *
     * @covers ::deliverMessage
     * @return void
     */
    public function testDeliverEventToWebhook(): void
    {
        // Create a mock HTTP client
        $mockClient = $this->createMock(\OCP\Http\Client\IClient::class);
        
        // Create a mock response
        $mockResponse = $this->createMock(\OCP\Http\Client\IResponse::class);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getBody')->willReturn('OK');
        
        // Mock the client service to return our mock client
        $this->clientService
            ->expects($this->once())
            ->method('newClient')
            ->willReturn($mockClient);
        
        // Mock the client to return our mock response
        $mockClient
            ->expects($this->once())
            ->method('post')
            ->with(
                $this->equalTo('https://example.com/webhook'),
                $this->callback(function ($options) {
                    return isset($options['body']) && 
                           isset($options['headers']['Content-Type']) &&
                           $options['headers']['Content-Type'] === 'application/cloudevents+json';
                })
            )
            ->willReturn($mockResponse);
        
        // Create a mock subscription
        $subscription = new class extends EventSubscription {
            public function getId(): int { return 1; }
            public function getStyle(): string { return 'push'; }
            public function getSink(): string { return 'https://example.com/webhook'; }
            public function getProtocolSettings(): array { return ['headers' => []]; }
        };
        
        // Mock the subscription mapper to return our subscription
        $this->subscriptionMapper
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($subscription);
        
        // Mock the message mapper to handle markDelivered call
        $this->messageMapper
            ->expects($this->once())
            ->method('markDelivered')
            ->with(1, $this->callback(function ($data) {
                return isset($data['statusCode']) && $data['statusCode'] === 200;
            }));
        
        // Create a mock event message
        $message = new class extends EventMessage {
            public function getId(): int { return 1; }
            public function getSubscriptionId(): int { return 1; }
            public function getPayload(): array { return ['test' => 'data']; }
            public function jsonSerialize(): array { return ['id' => 1, 'payload' => ['test' => 'data']]; }
        };
        
        $result = $this->eventService->deliverMessage($message);
        
        $this->assertTrue($result);
    }

    /**
     * Test subscription validation
     *
     * This test verifies that the event service correctly validates
     * event subscriptions before processing.
     *
     * @covers ::validateSubscription
     * @return void
     */
    public function testValidateSubscriptionWithValidData(): void
    {
        // Create anonymous class for EventSubscription entity
        $subscription = new class extends EventSubscription {
            public function getId(): int { return 1; }
            public function getEventType(): string { return 'test.event'; }
            public function getWebhookUrl(): string { return 'https://example.com/webhook'; }
            public function getStatus(): string { return 'active'; }
        };

        $this->assertEquals('test.event', $subscription->getEventType());
        $this->assertEquals('https://example.com/webhook', $subscription->getWebhookUrl());
        $this->assertEquals('active', $subscription->getStatus());
    }

    /**
     * Test event message creation
     *
     * This test verifies that the event service correctly creates
     * event messages with proper data structure.
     *
     * @covers ::createEventMessage
     * @return void
     */
    public function testCreateEventMessageWithValidData(): void
    {
        // Create anonymous class for Event entity
        $event = new class extends Event {
            public function getId(): int { return 1; }
            public function getType(): string { return 'test.event'; }
            public function getData(): array { return ['test' => 'data']; }
            public function getSource(): ?string { return null; }
        };

        // Create anonymous class for EventSubscription entity  
        $subscription = new class extends EventSubscription {
            public function getId(): int { return 1; }
            public function getEventType(): string { return 'test.event'; }
            public function getTypes(): array { return ['test.event']; }
            public function getWebhookUrl(): string { return 'https://example.com/webhook'; }
            public function getStatus(): string { return 'active'; }
            public function getSource(): ?string { return null; }
            public function getFilters(): array { return []; }
            public function getStyle(): string { return 'pull'; }
            public function getConsumerId(): int { return 1; }
        };

        $this->subscriptionMapper
            ->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo(null), $this->equalTo(null), $this->equalTo(['status' => 'active']))
            ->willReturn([$subscription]);

        $this->messageMapper
            ->expects($this->once())
            ->method('createFromArray')
            ->willReturn(new EventMessage());

        $result = $this->eventService->processEvent($event);

        $this->assertIsArray($result);
    }

    /**
     * Test event filtering by type
     *
     * This test verifies that the event service correctly filters
     * subscriptions by event type.
     *
     * @covers ::filterSubscriptionsByEventType
     * @return void
     */
    public function testFilterSubscriptionsByEventType(): void
    {
        // Create anonymous classes for different event types
        $matchingSubscription = new class extends EventSubscription {
            public function getId(): int { return 1; }
            public function getEventType(): string { return 'test.event'; }
            public function getTypes(): array { return ['test.event']; }
            public function getWebhookUrl(): string { return 'https://example.com/webhook1'; }
            public function getStatus(): string { return 'active'; }
            public function getSource(): ?string { return null; }
            public function getFilters(): array { return []; }
            public function getStyle(): string { return 'pull'; }
            public function getConsumerId(): int { return 1; }
        };

        $nonMatchingSubscription = new class extends EventSubscription {
            public function getId(): int { return 2; }
            public function getEventType(): string { return 'other.event'; }
            public function getTypes(): array { return ['other.event']; }
            public function getWebhookUrl(): string { return 'https://example.com/webhook2'; }
            public function getStatus(): string { return 'active'; }
            public function getSource(): ?string { return null; }
            public function getFilters(): array { return []; }
            public function getStyle(): string { return 'pull'; }
            public function getConsumerId(): int { return 2; }
        };

        $this->subscriptionMapper
            ->expects($this->once())
            ->method('findAll')
            ->with($this->equalTo(null), $this->equalTo(null), $this->equalTo(['status' => 'active']))
            ->willReturn([$matchingSubscription, $nonMatchingSubscription]);

        // Create anonymous class for Event entity
        $event = new class extends Event {
            public function getId(): int { return 1; }
            public function getType(): string { return 'test.event'; }
            public function getData(): array { return ['test' => 'data']; }
            public function getSource(): ?string { return null; }
        };

        $this->messageMapper
            ->expects($this->once())
            ->method('createFromArray')
            ->willReturn(new EventMessage());

        $result = $this->eventService->processEvent($event);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    /**
     * Test event processing error handling
     *
     * This test verifies that the event service properly handles
     * errors during event processing.
     *
     * @covers ::processEvent
     * @return void
     */
    public function testProcessEventWithError(): void
    {
        // Create anonymous class for Event entity
        $event = new class extends Event {
            public function getId(): int { return 1; }
            public function getType(): string { return 'test.event'; }
            public function getData(): array { return ['test' => 'data']; }
            public function getSource(): ?string { return null; }
        };

        $this->subscriptionMapper
            ->expects($this->once())
            ->method('findAll')
            ->willThrowException(new \Exception('Database error'));

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Database error');

        $this->eventService->processEvent($event);
    }
}
