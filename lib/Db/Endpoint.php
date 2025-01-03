<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

class Endpoint extends Entity implements JsonSerializable
{
    protected ?string   $uuid = null;
	protected ?string   $name = null; // The name of the endpoint
	protected ?string   $description = null; // The description of the endpoint
	protected ?string   $reference = null; // The reference of the endpoint
	protected ?string   $version = '0.0.0'; // The version of the endpoint
	protected ?string   $endpoint = null; // The actual endpoint e.g /api/buildings/{{id}}. An endpoint may contain parameters e.g {{id}}
	protected ?array    $endpointArray = null; // An array representation of the endpoint. Automatically generated
	protected ?string   $endpointRegex = null; // A regex representation of the endpoint. Automatically generated
	protected ?string   $method = null; // One of GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD. method and endpoint combination should be unique
	protected ?string   $targetType = null; // The target to attach this endpoint to, should be one of source (to create a proxy endpoint) or register/schema (to create an object endpoint) or job (to fire an event) or synchronization (to create a synchronization endpoint)
	protected ?string   $targetId = null; // The target id to attach this endpoint to

	protected array $conditions = [];
	protected ?DateTime $created = null;
	protected ?DateTime $updated = null;

	public function __construct() {
        $this->addType(fieldName:'uuid', type: 'string');
		$this->addType(fieldName:'name', type: 'string');
		$this->addType(fieldName:'description', type: 'string');
		$this->addType(fieldName:'reference', type: 'string');
		$this->addType(fieldName:'version', type: 'string');
		$this->addType(fieldName:'endpoint', type: 'string');
		$this->addType(fieldName:'endpointArray', type: 'json');
		$this->addType(fieldName:'endpointRegex', type: 'string');
		$this->addType(fieldName:'method', type: 'string');
		$this->addType(fieldName:'targetType', type: 'string');
		$this->addType(fieldName:'targetId', type: 'string');
		$this->addType(fieldName:'conditions', type: 'json');
		$this->addType(fieldName:'created', type: 'datetime');
		$this->addType(fieldName:'updated', type: 'datetime');
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
				// ("Error writing $key");
			}
		}

		return $this;
	}

	public function jsonSerialize(): array
	{
		return [
			'id' => $this->id,
            'uuid' => $this->uuid,
			'name' => $this->name,
			'description' => $this->description,
			'reference' => $this->reference,
			'version' => $this->version,
			'endpoint' => $this->endpoint,
			'endpointArray' => $this->endpointArray,
			'endpointRegex' => $this->endpointRegex,
			'method' => $this->method,
			'targetType' => $this->targetType,
			'targetId' => $this->targetId,
			'conditions' => $this->conditions,
			'created' => isset($this->created) ? $this->created->format('c') : null,
			'updated' => isset($this->updated) ? $this->updated->format('c') : null,

		];
	}
}
