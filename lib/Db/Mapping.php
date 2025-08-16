<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * Class Mapping
 *
 * Represents a mapping configuration entity that defines how to transform data between different formats.
 *
 * @package OCA\OpenConnector\Db
 * @category Database
 * @author OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license AGPL-3.0
 * @version 1.0.0
 * @link https://github.com/OpenConnector/openconnector
 */
class Mapping extends Entity implements JsonSerializable
{
    protected ?string $uuid = null;
	protected ?string $reference = null;
	protected ?string $version = '0.0.0';
	protected ?string $name = null;
	protected ?string $description = null;
	protected ?array $mapping = [];
	protected ?array $unset = [];
	protected ?array $cast = [];
	protected ?bool $passThrough = null;
	protected ?DateTime $dateCreated = null;
	protected ?DateTime $dateModified = null;
	protected ?array $configurations = []; // Array of configuration IDs that this mapping belongs to
	protected ?string $slug = null;
	protected ?DateTime $expires = null; // Expiration date for this entity
	protected ?int $size = null; // Size of this entity in bytes

	/**
	 * Get the mapping configuration
	 *
	 * @return array The mapping configuration or empty array if null
	 */
	public function getMapping(): array
	{
		return $this->mapping ?? [];
	}

	/**
	 * Get the unset configuration
	 *
	 * @return array The unset configuration or empty array if null
	 */
	public function getUnset(): array
	{
		return $this->unset ?? [];
	}

	/**
	 * Get the cast configuration
	 *
	 * @return array The cast configuration or empty array if null
	 */
	public function getCast(): array
	{
		return $this->cast ?? [];
	}

	public function __construct() {
        $this->addType('uuid', 'string');
		$this->addType('reference', 'string');
		$this->addType('version', 'string');
		$this->addType('name', 'string');
		$this->addType('description', 'string');
		$this->addType('mapping', 'json');
		$this->addType('unset', 'json');
		$this->addType('cast', 'json');
		$this->addType('passThrough', 'boolean');
		$this->addType('dateCreated', 'datetime');
		$this->addType('dateModified', 'datetime');
		$this->addType('configurations', 'json');
		$this->addType('slug', 'string');
		$this->addType('expires', 'datetime');
		$this->addType('size', 'integer');
	}

	public function getJsonFields(): array
	{
		return array_keys(
			array_filter($this->getFieldTypes(), function ($field) {
				return $field === 'json';
			})
		);
	}

    public function getUpdated(): ?DateTime
    {
        return $this->dateModified;
    }


	/**
	 * Get the slug for the endpoint.
	 * If the slug is not set, generate one from the name.
	 *
	 * @return string The slug for the endpoint
	 * @phpstan-return non-empty-string
	 * @psalm-return non-empty-string
	 */
	public function getSlug(): string
	{
		// Check if the slug is already set
		if (!empty($this->slug)) {
			return $this->slug;
		}

		// Generate a slug from the name if not set
		// Convert the name to lowercase, replace spaces with hyphens, and remove non-alphanumeric characters
		$generatedSlug = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($this->name)));

		// Ensure the generated slug is not empty
		if (empty($generatedSlug)) {
			throw new \RuntimeException('Unable to generate a valid slug from the name.');
		}

		return $generatedSlug;
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
//				("Error writing $key");
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
			'version' => $this->version,
			'reference' => $this->reference,
			'mapping' => $this->mapping,
			'unset' => $this->unset,
			'cast' => $this->cast,
			'passThrough' => $this->passThrough,
			'configurations' => $this->configurations,
			'dateCreated' => isset($this->dateCreated) ? $this->dateCreated->format('c') : null,
			'dateModified' => isset($this->dateModified) ? $this->dateModified->format('c') : null,
			'slug' => $this->getSlug(),
			'expires' => isset($this->expires) ? $this->expires->format('c') : null,
			'size' => $this->size,
		];
	}
}
