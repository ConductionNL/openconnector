<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * Entity class representing a synchronization log entry
 */
class SynchronizationLog extends Entity implements JsonSerializable
{
    protected ?string $uuid = null;
    protected ?string $message = null;
    protected ?string $synchronizationId = null;
    protected ?array $result = [];
    protected ?string $userId = null;
    protected ?string $sessionId = null;
    protected bool $test = false;
    protected bool $force = false;
    protected int $executionTime = 0;
    protected ?DateTime $created = null;
    protected ?DateTime $expires = null;

    /**
     * Get the synchronization result
     *
     * @return array The result data or empty array if null
     */
    public function getResult(): array
    {
        return $this->result ?? [];
    }

    public function __construct() {
        $this->addType('uuid', 'string');
        $this->addType('message', 'string');
        $this->addType('synchronizationId', 'string');
        $this->addType('result', 'json');
        $this->addType('userId', 'string');
        $this->addType('sessionId', 'string');
        $this->addType('test', 'boolean');
        $this->addType('force', 'boolean');
        $this->addType('executionTime', 'integer');
        $this->addType('created', 'datetime');
        $this->addType('expires', 'datetime');
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
            'message' => $this->message,
            'synchronizationId' => $this->synchronizationId,
            'result' => $this->result,
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'test' => $this->test,
            'force' => $this->force,
            'executionTime' => $this->executionTime,
            'created' => isset($this->created) ? $this->created->format('c') : null,
            'expires' => isset($this->expires) ? $this->expires->format('c') : null,
        ];
    }
}
