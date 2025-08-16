<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class CallLog extends Entity implements JsonSerializable
{
    /** @var string|null $uuid Unique identifier for this call log entry */
    protected ?string $uuid = null;

    /** @var int|null $statusCode HTTP status code returned from the API call */
    protected ?int $statusCode = null;

    /** @var string|null $statusMessage Status message or description returned with the response */
    protected ?string $statusMessage = null;

    /** @var array|null $request Complete request data including headers, method, body, etc. */
    protected ?array $request = null;

    /** @var array|null $response Complete response data including headers, body, and status info */
    protected ?array $response = null;

    /** @var int|null $sourceId Reference to the source/endpoint that was called */
    protected ?int $sourceId = null;

    /** @var int|null $actionId Reference to the action that triggered this call */
    protected ?int $actionId = null;

    /** @var int|null $synchronizationId Reference to the synchronization process if this call is part of one */
    protected ?int $synchronizationId = null;

    /** @var string|null $userId Identifier of the user who initiated the call */
    protected ?string $userId = null;

    /** @var string|null $sessionId Session identifier associated with this call */
    protected ?string $sessionId = null;

    /** @var int|null $size Size of the log entry in bytes for retention management */
    protected ?int $size = null;

    /** @var DateTime|null $expires When this log entry should expire/be deleted */
    protected ?DateTime $expires = null;

    /** @var DateTime|null $created When this log entry was created */
    protected ?DateTime $created = null;

    /**
     * Get the request data
     *
     * @return array The request data or null
     */
    public function getRequest(): ?array
    {
        return $this->request;
    }

    /**
     * Get the response data
     *
     * @return array The response data or null
     */
    public function getResponse(): ?array
    {
        return $this->response;
    }

    public function __construct() {
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
        $this->addType('size', 'integer');
        $this->addType('expires', 'datetime');
        $this->addType('created', 'datetime');
    }

    public function getJsonFields(): array
    {
        return array_keys(
            array_filter($this->getFieldTypes(), function ($field) {
                return $field === 'json';
            })
        );
    }

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
                // Handle or log the exception if needed
            }
        }

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'statusCode' => $this->statusCode,
            'statusMessage' => $this->statusMessage,
            'request' => $this->request,
            'response' => $this->response,
            'sourceId' => $this->sourceId,
            'actionId' => $this->actionId,
            'synchronizationId' => $this->synchronizationId,            
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'size' => $this->size,
            'expires' => isset($this->expires) ? $this->expires->format('c') : null,
            'created' => isset($this->created) ? $this->created->format('c') : null,
            
        ];
    }
}
