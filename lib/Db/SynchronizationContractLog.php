<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class SynchronizationContractLog extends Entity implements JsonSerializable
{
    protected ?string $uuid = null;
	protected ?string $message = null;
    protected ?string $synchronizationId = null;
    protected ?string $synchronizationContractId = null;
    protected ?array $source = [];
    protected ?array $target = [];
    protected ?string $userId = null;
    protected ?string $sessionId = null;
    protected ?DateTime $expires = null;
    protected ?DateTime $created = null;

    public function __construct() {
        $this->addType('uuid', 'string');
		$this->addType('message', 'string');
        $this->addType('synchronizationId', 'string');
        $this->addType('synchronizationContractId', 'string');
        $this->addType('source', 'json');
        $this->addType('target', 'json');
        $this->addType('userId', 'string');
        $this->addType('sessionId', 'string');
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
            'source' => $this->source,
            'target' => $this->target,
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'expires' => isset($this->expires) ? $this->expires->format('c') : null,
            'created' => isset($this->created) ? $this->created->format('c') : null,
        ];
    }
}
