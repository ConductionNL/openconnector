<?php
/**
 * OpenConnector Event Entity
 *
 * This file contains the entity class for event data in the OpenConnector
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
 * This class implements the CloudEvents specification (https://cloudevents.io/)
 * for events generated when objects are updated in open registers.
 */
class Event extends Entity implements JsonSerializable
{

    // Required CloudEvent attributes.

    /**
     * Unique UUID identifier for the event.
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * URI identifying the context where event happened.
     *
     * @var string|null
     */
    protected ?string $source = null;

    /**
     * Event type identifier.
     *
     * @var string|null
     */
    protected ?string $type = null;

    /**
     * CloudEvents specification version.
     *
     * @var string|null
     */
    protected ?string $specversion = '1.0';

    /**
     * Timestamp of when the event occurred.
     *
     * @var DateTime|null
     */
    protected ?DateTime $time = null;

    // Optional CloudEvent attributes.

    /**
     * Content type of data.
     *
     * @var string|null
     */
    protected ?string $datacontenttype = 'application/json';

    /**
     * URI to the schema that data adheres to.
     *
     * @var string|null
     */
    protected ?string $dataschema = null;

    /**
     * Subject of the event.
     *
     * @var string|null
     */
    protected ?string $subject = null;

    /**
     * Event payload.
     *
     * @var array|null
     */
    protected ?array $data = [];

    // Additional tracking fields.

    /**
     * User who triggered the event.
     *
     * @var string|null
     */
    protected ?string $userId = null;

    /**
     * When the event was created in our system.
     *
     * @var DateTime|null
     */
    protected ?DateTime $created = null;

    /**
     * When the event was last updated.
     *
     * @var DateTime|null
     */
    protected ?DateTime $updated = null;

    /**
     * When the event was processed.
     *
     * @var DateTime|null
     */
    protected ?DateTime $processed = null;

    /**
     * Event processing status.
     *
     * @var string|null
     */
    protected ?string $status = 'pending';


    /**
     * Get the event data payload.
     *
     * @return array The event data or empty array if null
     */
    public function getData(): array
    {
        return ($this->data ?? []);

    }//end getData()


    /**
     * Event constructor.
     * Initializes the field types for the Event entity.
     *
     * @return void
     */
    public function __construct()
    {
        $this->addType('uuid', 'string');
        $this->addType('source', 'string');
        $this->addType('type', 'string');
        $this->addType('specversion', 'string');
        $this->addType('time', 'datetime');
        $this->addType('datacontenttype', 'string');
        $this->addType('dataschema', 'string');
        $this->addType('subject', 'string');
        $this->addType('data', 'json');
        $this->addType('userId', 'string');
        $this->addType('created', 'datetime');
        $this->addType('updated', 'datetime');
        $this->addType('processed', 'datetime');
        $this->addType('status', 'string');

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
        $time = null;
        if (isset($this->time) === true) {
            $time = $this->time->format('c');
        }

        $created = null;
        if (isset($this->created) === true) {
            $created = $this->created->format('c');
        }

        $updated = null;
        if (isset($this->updated) === true) {
            $updated = $this->updated->format('c');
        }

        $processed = null;
        if (isset($this->processed) === true) {
            $processed = $this->processed->format('c');
        }

        return [
            'id'              => $this->id,
            'uuid'            => $this->uuid,
            'source'          => $this->source,
            'type'            => $this->type,
            'specversion'     => $this->specversion,
            'time'            => $time,
            'datacontenttype' => $this->datacontenttype,
            'dataschema'      => $this->dataschema,
            'subject'         => $this->subject,
            'data'            => $this->data,
            'userId'          => $this->userId,
            'created'         => $created,
            'updated'         => $updated,
            'processed'       => $processed,
            'status'          => $this->status,
        ];

    }//end jsonSerialize()


}//end class
