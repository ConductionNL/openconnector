<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * Class SynchronizationContractLog
 * 
 * Entity class representing a synchronization contract log entry
 */
class SynchronizationContractLog extends Entity implements JsonSerializable
{
    protected ?string $uuid = null;
	protected ?string $message = null;
    protected ?string $synchronizationId = null;
    protected ?string $synchronizationContractId = null;
    protected ?string $synchronizationLogId = null;
    protected ?array $source = [];
    protected ?array $target = [];
    protected ?string $targetResult = null; // CRUD action taken on target (create/read/update/delete)
    protected ?string $userId = null;
    protected ?string $sessionId = null;
    protected ?bool $test = false;
    protected ?bool $force = false;
    protected ?int $size = null;
    protected ?DateTime $expires = null;
    protected ?DateTime $created = null;

    /**
     * Get the source data
     *
     * @return array The source data or null
     */
    public function getSource(): ?array
    {
        return $this->source;
    }

    /**
     * Get the target data
     *
     * @return array The target data or null
     */
    public function getTarget(): ?array
    {
        return $this->target;
    }

    public function __construct() {
        $this->addType('uuid', 'string');
		$this->addType('message', 'string');
        $this->addType('synchronizationId', 'string');
        $this->addType('synchronizationContractId', 'string');
        $this->addType('synchronizationLogId', 'string');
        $this->addType('source', 'json');
        $this->addType('target', 'json');
        $this->addType('targetResult', 'string');
        $this->addType('userId', 'string');
        $this->addType('sessionId', 'string');
        $this->addType('test', 'boolean');
        $this->addType('force', 'boolean');
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
			'message' => $this->message,
            'synchronizationId' => $this->synchronizationId,
            'synchronizationContractId' => $this->synchronizationContractId,
            'synchronizationLogId' => $this->synchronizationLogId,
            'source' => $this->source,
            'target' => $this->target,
            'targetResult' => $this->targetResult,
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'test' => $this->test,
            'force' => $this->force,
            'size' => $this->size,
            'expires' => isset($this->expires) ? $this->expires->format('c') : null,
            'created' => isset($this->created) ? $this->created->format('c') : null,
        ];
    }
}
