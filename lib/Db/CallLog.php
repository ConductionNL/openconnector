<?php
/**
 * OpenConnector CallLog Entity
 *
 * This file contains the entity class for call log data in the OpenConnector
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
 * Class CallLog
 *
 * A call log represents a record of an API call made to an external system.
 * It contains details about the request, response, and metadata about the call.
 *
 * @package OCA\OpenConnector\Db
 */
class CallLog extends Entity implements JsonSerializable
{

    /**
     * Unique identifier for this call log entry
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * HTTP status code returned from the API call
     *
     * @var integer|null
     */
    protected ?int $statusCode = null;

    /**
     * Status message or description returned with the response
     *
     * @var string|null
     */
    protected ?string $statusMessage = null;

    /**
     * Complete request data including headers, method, body, etc.
     *
     * @var array|null
     */
    protected ?array $request = null;

    /**
     * Complete response data including headers, body, and status info
     *
     * @var array|null
     */
    protected ?array $response = null;

    /**
     * Reference to the source/endpoint that was called
     *
     * @var integer|null
     */
    protected ?int $sourceId = null;

    /**
     * Reference to the action that triggered this call
     *
     * @var integer|null
     */
    protected ?int $actionId = null;

    /**
     * Reference to the synchronization process if this call is part of one
     *
     * @var integer|null
     */
    protected ?int $synchronizationId = null;

    /**
     * Identifier of the user who initiated the call
     *
     * @var string|null
     */
    protected ?string $userId = null;

    /**
     * Session identifier associated with this call
     *
     * @var string|null
     */
    protected ?string $sessionId = null;

    /**
     * When this log entry should expire/be deleted
     *
     * @var DateTime|null
     */
    protected ?DateTime $expires = null;

    /**
     * When this log entry was created
     *
     * @var DateTime|null
     */
    protected ?DateTime $created = null;


    /**
     * Get the request data
     *
     * @return array|null The request data or null
     */
    public function getRequest(): ?array
    {
        return $this->request;

    }//end getRequest()


    /**
     * Get the response data
     *
     * @return array|null The response data or null
     */
    public function getResponse(): ?array
    {
        return $this->response;

    }//end getResponse()


    /**
     * CallLog constructor.
     * Initializes the field types for the CallLog entity.
     *
     * @return void
     */
    public function __construct()
    {
        $this->addType('uuid', 'string');
        $this->addType('statusCode', 'integer');
        $this->addType('statusMessage', 'string');
        $this->addType('request', 'json');
        $this->addType('response', 'json');
        $this->addType('sourceId', 'integer');
        $this->addType('actionId', 'integer');
        $this->addType('synchronizationId', 'integer');
        $this->addType('userId', 'string');
        $this->addType('sessionId', 'string');
        $this->addType('expires', 'datetime');
        $this->addType('created', 'datetime');

    }//end __construct()


    /**
     * Get the JSON fields of the CallLog entity.
     *
     * @return array An array of field names that are of type 'json'
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
     * Hydrate the CallLog entity with data from an array.
     *
     * @param array $object The array containing the data to hydrate the entity
     *
     * @return self Returns the hydrated CallLog entity
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
     * Serialize the CallLog entity to JSON.
     *
     * @return array An array representation of the CallLog entity for JSON serialization
     */
    public function jsonSerialize(): array
    {
        $expires = null;
        if (isset($this->expires) === true) {
            $expires = $this->expires->format('c');
        }

        $created = null;
        if (isset($this->created) === true) {
            $created = $this->created->format('c');
        }

        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'statusCode'        => $this->statusCode,
            'statusMessage'     => $this->statusMessage,
            'request'           => $this->request,
            'response'          => $this->response,
            'sourceId'          => $this->sourceId,
            'actionId'          => $this->actionId,
            'synchronizationId' => $this->synchronizationId,
            'userId'            => $this->userId,
            'sessionId'         => $this->sessionId,
            'expires'           => $expires,
            'created'           => $created,
        ];

    }//end jsonSerialize()


}//end class
