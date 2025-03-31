<?php
/**
 * OpenConnector EventSubscription Entity
 *
 * This file contains the entity class for event subscription data in the OpenConnector
 * application.
 *
 * @category  Entity
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Db;

use DateTime;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * Class EventSubscription
 *
 * Represents a subscription to events in the system, following the CloudEvents specification.
 * Supports both push and pull delivery styles and configurable filtering logic.
 */
class EventSubscription extends Entity implements JsonSerializable
{

    /**
     * Unique identifier for the subscription.
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * Reference identifier for the subscription.
     *
     * @var string|null
     */
    protected ?string $reference = null;

    /**
     * Version of the subscription.
     *
     * @var string|null
     */
    protected ?string $version = '0.0.0';

    /**
     * URI identifying the context where events originate.
     *
     * @var string|null
     */
    protected ?string $source = null;

    /**
     * Array of CloudEvent type values to subscribe to.
     *
     * @var array|null
     */
    protected ?array $types = [];

    /**
     * Subscription manager specific configuration.
     *
     * @var array|null
     */
    protected ?array $config = [];

    /**
     * Array of filter expressions for event matching.
     *
     * @var array|null
     */
    protected ?array $filters = [];

    /**
     * URI where events should be delivered.
     *
     * @var string|null
     */
    protected ?string $sink = null;

    /**
     * Delivery protocol (HTTP, MQTT, AMQP, etc.).
     *
     * @var string|null
     */
    protected ?string $protocol = null;

    /**
     * Protocol-specific settings.
     *
     * @var array|null
     */
    protected ?array $protocolSettings = [];

    /**
     * Delivery style ('push' or 'pull').
     *
     * @var string|null
     */
    protected ?string $style = 'push';

    /**
     * Subscription status.
     *
     * @var string|null
     */
    protected ?string $status = 'active';

    /**
     * Owner of the subscription.
     *
     * @var string|null
     */
    protected ?string $userId = null;

    /**
     * Creation timestamp.
     *
     * @var DateTime|null
     */
    protected ?DateTime $created = null;

    /**
     * Last update timestamp.
     *
     * @var DateTime|null
     */
    protected ?DateTime $updated = null;


    /**
     * Get the event types to subscribe to.
     *
     * @return array The event types or empty array if null
     */
    public function getTypes(): array
    {
        return ($this->types ?? []);

    }//end getTypes()


    /**
     * Get the subscription configuration.
     *
     * @return array The configuration or empty array if null
     */
    public function getConfig(): array
    {
        return ($this->config ?? []);

    }//end getConfig()


    /**
     * Get the subscription filters.
     *
     * @return array The filters or empty array if null
     */
    public function getFilters(): array
    {
        return ($this->filters ?? []);

    }//end getFilters()


    /**
     * Get the protocol settings.
     *
     * @return array The protocol settings or empty array if null
     */
    public function getProtocolSettings(): array
    {
        return ($this->protocolSettings ?? []);

    }//end getProtocolSettings()


    /**
     * Constructor to set up data types for properties.
     *
     * @return void
     */
    public function __construct()
    {
        $this->addType('uuid', 'string');
        $this->addType(fieldName: 'reference', type: 'string');
        $this->addType(fieldName: 'version', type: 'string');
        $this->addType('source', 'string');
        $this->addType('types', 'json');
        $this->addType('config', 'json');
        $this->addType('filters', 'json');
        $this->addType('sink', 'string');
        $this->addType('protocol', 'string');
        $this->addType('protocolSettings', 'json');
        $this->addType('style', 'string');
        $this->addType('status', 'string');
        $this->addType('userId', 'string');
        $this->addType('created', 'datetime');
        $this->addType('updated', 'datetime');

    }//end __construct()


    /**
     * Get fields that should be JSON encoded.
     *
     * @return array<string> List of field names that are JSON type
     */
    public function getJsonFields(): array
    {
        return array_keys(
            array_filter(
                $this->getFieldTypes(),
                function ($field) {
                    return $field === 'json';
                }
            )
        );

    }//end getJsonFields()


    /**
     * Hydrate the entity from an array of data.
     *
     * @param array<string,mixed> $object Data to hydrate from
     *
     * @return self Returns the hydrated entity
     */
    public function hydrate(array $object): self
    {
        $jsonFields = $this->getJsonFields();

        foreach ($object as $key => $value) {
            if (in_array($key, $jsonFields) === true && $value === []) {
                $value = [];
            }

            $method = 'set'.ucfirst($key);

            try {
                $this->$method($value);
            } catch (\Exception $exception) {
                // Error writing $key.
            }
        }

        return $this;

    }//end hydrate()


    /**
     * Serialize the entity to JSON.
     *
     * @return array<string,mixed> The serialized entity data
     */
    public function jsonSerialize(): array
    {
        $created = null;
        if (isset($this->created) === true) {
            $created = $this->created->format('c');
        }

        $updated = null;
        if (isset($this->updated) === true) {
            $updated = $this->updated->format('c');
        }

        return [
            'id'               => $this->id,
            'uuid'             => $this->uuid,
            'reference'        => $this->reference,
            'version'          => $this->version,
            'source'           => $this->source,
            'types'            => $this->types,
            'config'           => $this->config,
            'filters'          => $this->filters,
            'sink'             => $this->sink,
            'protocol'         => $this->protocol,
            'protocolSettings' => $this->protocolSettings,
            'style'            => $this->style,
            'status'           => $this->status,
            'userId'           => $this->userId,
            'created'          => $created,
            'updated'          => $updated,
        ];

    }//end jsonSerialize()


}//end class
