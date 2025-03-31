<?php

/**
 * Service class for managing events and their delivery.
 *
 * This service is responsible for creating, processing, and delivering events to subscribers
 * based on configured rules and filters. It supports both push and pull delivery methods.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service;

use DateTime;
use Exception;
use OCA\OpenConnector\Db\Event;
use OCA\OpenConnector\Db\EventMapper;
use OCA\OpenConnector\Db\EventMessage;
use OCA\OpenConnector\Db\EventMessageMapper;
use OCA\OpenConnector\Db\EventSubscription;
use OCA\OpenConnector\Db\EventSubscriptionMapper;
use OCP\Http\Client\IClientService;
use Psr\Log\LoggerInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * Service class for managing events and their delivery.
 */
class EventService
{
    /**
     * Constructor for EventService.
     *
     * Initializes the service with required mappers and services for event management.
     *
     * @param EventMapper             $eventMapper        Mapper for event entities
     * @param EventMessageMapper      $messageMapper      Mapper for event message entities
     * @param EventSubscriptionMapper $subscriptionMapper Mapper for event subscription entities
     * @param IClientService          $clientService      HTTP client service for push delivery
     * @param LoggerInterface         $logger             Logger for error handling
     *
     * @return void
     */
    public function __construct(
        private readonly EventMapper $eventMapper,
        private readonly EventMessageMapper $messageMapper,
        private readonly EventSubscriptionMapper $subscriptionMapper,
        private readonly IClientService $clientService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Process a new event and create messages for all matching subscriptions.
     *
     * This method finds all active subscriptions, checks if they match the event,
     * creates corresponding event messages, and attempts delivery for push subscribers.
     *
     * @param Event $event The event to process
     *
     * @return array<EventMessage> Array of created messages
     * @throws Exception If an error occurs during processing
     */
    public function processEvent(Event $event): array
    {
        try {
            // Find all active subscriptions.
            $subscriptions = $this->subscriptionMapper->findAll(filters: ['status' => 'active']);
            $messages      = [];

            foreach ($subscriptions as $subscription) {
                if ($this->doesEventMatchSubscription($event, $subscription) === true) {
                    $message    = $this->createEventMessage($event, $subscription);
                    $messages[] = $message;

                    // If it's a push subscription, attempt immediate delivery.
                    if ($subscription->getStyle() === 'push') {
                        $this->deliverMessage($message);
                    }
                }
            }

            return $messages;
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to process event: ' . $e->getMessage(),
                [
                    'exception' => $e,
                    'event'     => $event->jsonSerialize(),
                ]
            );
            throw $e;
        }
    }

    /**
     * Check if an event matches a subscription's criteria
     *
     * @param  Event             $event        The event to check
     * @param  EventSubscription $subscription The subscription to match against
     *
     * @return bool Whether the event matches the subscription
     */
    private function doesEventMatchSubscription(Event $event, EventSubscription $subscription): bool
    {
        // Check event type matches
        if (
            empty($subscription->getTypes() === false)
            && in_array($event->getType(), $subscription->getTypes()) === false
        ) {
            return false;
        }

        // Check source matches
        if (
            $subscription->getSource()
            && $event->getSource() !== $subscription->getSource()
        ) {
            return false;
        }

        // Process filters if any exist
        if (empty($subscription->getFilters()) === false) {
            return $this->evaluateFilters($event, $subscription->getFilters());
        }

        return true;
    }

    /**
     * Evaluate filter conditions against an event
     *
     * @param  Event       $event   The event to evaluate
     * @param  array<mixed> $filters The filters to apply
     *
     * @return bool Whether the event passes all filters
     */
    private function evaluateFilters(Event $event, array $filters): bool
    {
        $expressionLanguage = new ExpressionLanguage();

        foreach ($filters as $filter) {
            foreach ($filter as $dialect => $condition) {
                switch ($dialect) {
                    case 'exact':
                        foreach ($condition as $field => $value) {
                            if ($event->{'get' . ucfirst($field)}() !== $value) {
                                return false;
                            }
                        }
                        break;

                    case 'prefix':
                        foreach ($condition as $field => $value) {
                            if (str_starts_with($event->{'get' . ucfirst($field)}(), $value) === false) {
                                return false;
                            }
                        }
                        break;

                    case 'suffix':
                        foreach ($condition as $field => $value) {
                            if (str_ends_with($event->{'get' . ucfirst($field)}(), $value) === false) {
                                return false;
                            }
                        }
                        break;

                    case 'expression':
                        $variables = $event->jsonSerialize();
                        if ($expressionLanguage->evaluate($condition, $variables) === false) {
                            return false;
                        }
                        break;
                }
            }
        }

        return true;
    }

    /**
     * Create a new event message
     *
     * @param  Event             $event        The event to create a message for
     * @param  EventSubscription $subscription The subscription to associate with the message
     *
     * @return EventMessage The created event message
     */
    private function createEventMessage(Event $event, EventSubscription $subscription): EventMessage
    {
        return $this->messageMapper->createFromArray(
            [
                'eventId'        => $event->getId(),
                'consumerId'     => $subscription->getConsumerId(),
                'subscriptionId' => $subscription->getId(),
                'status'         => 'pending',
                'payload'        => $event->jsonSerialize(),
                'created'        => new DateTime(),
                'updated'        => new DateTime(),
            ]
        );
    }

    /**
     * Attempt to deliver a message
     *
     * @param  EventMessage $message The message to deliver
     *
     * @return bool Whether delivery was successful
     */
    public function deliverMessage(EventMessage $message): bool
    {
        try {
            $subscription = $this->subscriptionMapper->find($message->getSubscriptionId());

            if ($subscription->getStyle() !== 'push') {
                return false;
            }

            $client   = $this->clientService->newClient();
            $response = $client->post(
                $subscription->getSink(),
                [
                    'body'    => json_encode($message->getPayload()),
                    'headers' => [
                        'Content-Type' => 'application/cloudevents+json',
                        ...($subscription->getProtocolSettings()['headers'] ?? [])
                    ],
                    'timeout' => 30,
                ]
            );

            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                $this->messageMapper->markDelivered(
                    $message->getId(),
                    [
                        'statusCode' => $response->getStatusCode(),
                        'body'       => $response->getBody(),
                    ]
                );
                return true;
            }

            throw new Exception('Delivery failed with status code: ' . $response->getStatusCode());
        } catch (Exception $e) {
            $this->logger->error(
                'Failed to deliver message: ' . $e->getMessage(),
                [
                    'exception' => $e,
                    'message'   => $message->jsonSerialize(),
                ]
            );

            $this->messageMapper->markFailed(
                $message->getId(),
                [
                    'error' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * Process pending message retries
     *
     * @param  int $maxRetries Maximum number of retry attempts
     *
     * @return int Number of successfully delivered messages
     */
    public function processRetries(int $maxRetries = 5): int
    {
        $messages     = $this->messageMapper->findPendingRetries($maxRetries);
        $successCount = 0;

        foreach ($messages as $message) {
            if ($this->deliverMessage($message)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Get events for a pull-based subscription
     *
     * @param  EventSubscription $subscription The subscription to pull events for
     * @param  int|null          $limit        Maximum number of events to return, defaults to 100
     * @param  string|null       $cursor       Pagination cursor, defaults to null
     *
     * @return array{messages: EventMessage[], cursor: string|null} Array with messages and cursor
     */
    public function pullEvents(EventSubscription $subscription, ?int $limit = 100, ?string $cursor = null): array
    {
        $filters = [
            'subscriptionId' => $subscription->getId(),
            'status'         => 'pending',
        ];

        if ($cursor) {
            $filters['id'] = ['>' => $cursor];
        }

        $messages   = $this->messageMapper->findAll($limit, 0, $filters);
        $lastCursor = end($messages) ? (string) end($messages)->getId() : null;

        return [
            'messages' => $messages,
            'cursor'   => $lastCursor,
        ];
    }

    /**
     * Handle object creation by creating and processing a CloudEvent
     *
     * @param  object $object The created object
     *
     * @return array<EventMessage> The created CloudEvent messages
     * @throws Exception If event processing fails
     */
    public function handleObjectCreated(object $object): array
    {
        $event = $this->eventMapper->createFromArray(
            [
                'source'  => '/objects/' . $object->getType(),
                'type'    => 'com.nextcloud.openregister.object.created',
                'time'    => new DateTime(),
                'subject' => $object->getId(),
                'data'    => [
                    'type'       => $object->getType(),
                    'id'         => $object->getId(),
                    'attributes' => $object->getAttributes(),
                ],
                'userId'  => $object->getUserId(),
            ]
        );

        return $this->processEvent($event);
    }

    /**
     * Handle object update by creating and processing a CloudEvent
     *
     * @param  object $oldObject The previous state of the object
     * @param  object $newObject The new state of the object
     *
     * @return array<EventMessage> The created CloudEvent messages
     * @throws Exception If event processing fails
     */
    public function handleObjectUpdated(object $oldObject, object $newObject): array
    {
        $event = $this->eventMapper->createFromArray(
            [
                'source'  => '/objects/' . $newObject->getType(),
                'type'    => 'com.nextcloud.openregister.object.updated',
                'time'    => new DateTime(),
                'subject' => $newObject->getId(),
                'data'    => [
                    'type'       => $newObject->getType(),
                    'id'         => $newObject->getId(),
                    'attributes' => $newObject->getAttributes(),
                    'previous'   => [
                        'attributes' => $oldObject->getAttributes(),
                    ],
                ],
                'userId'  => $newObject->getUserId(),
            ]
        );

        return $this->processEvent($event);
    }

    /**
     * Handle object deletion by creating and processing a CloudEvent
     *
     * @param  object $object The deleted object
     *
     * @return array<EventMessage> The created CloudEvent messages
     * @throws Exception If event processing fails
     */
    public function handleObjectDeleted(object $object): array
    {
        $event = $this->eventMapper->createFromArray(
            [
                'source'  => '/objects/' . $object->getType(),
                'type'    => 'com.nextcloud.openregister.object.deleted',
                'time'    => new DateTime(),
                'subject' => $object->getId(),
                'data'    => [
                    'type' => $object->getType(),
                    'id'   => $object->getId(),
                ],
                'userId'  => $object->getUserId(),
            ]
        );

        return $this->processEvent($event);
    }
}
