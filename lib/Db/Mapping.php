<?php
/**
 * OpenConnector Mapping Entity
 *
 * This file contains the entity class for mapping data in the OpenConnector
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
 * Class Mapping
 *
 * A mapping represents a configuration for transforming data from one format to another.
 * It includes rules for field mapping, type casting, and field removal.
 *
 * @package OCA\OpenConnector\Db
 */
class Mapping extends Entity implements JsonSerializable
{

    /**
     * The unique identifier of the mapping.
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * The reference identifier for the mapping.
     *
     * @var string|null
     */
    protected ?string $reference = null;

    /**
     * The version of the mapping.
     *
     * @var string|null
     */
    protected ?string $version = '0.0.0';

    /**
     * The name of the mapping.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The description of the mapping.
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * The mapping configuration.
     *
     * @var array|null
     */
    protected ?array $mapping = [];

    /**
     * The unset configuration.
     *
     * @var array|null
     */
    protected ?array $unset = [];

    /**
     * The cast configuration.
     *
     * @var array|null
     */
    protected ?array $cast = [];

    /**
     * Indicates if the mapping is a pass-through.
     *
     * @var boolean|null
     */
    protected ?bool $passThrough = null;

    /**
     * The date and time the mapping was created.
     *
     * @var DateTime|null
     */
    protected ?DateTime $dateCreated = null;

    /**
     * The date and time the mapping was last modified.
     *
     * @var DateTime|null
     */
    protected ?DateTime $dateModified = null;


    /**
     * Get the mapping configuration
     *
     * @return array The mapping configuration or empty array if null
     */
    public function getMapping(): array
    {
        return ($this->mapping ?? []);

    }//end getMapping()


    /**
     * Get the unset configuration
     *
     * @return array The unset configuration or empty array if null
     */
    public function getUnset(): array
    {
        return ($this->unset ?? []);

    }//end getUnset()


    /**
     * Get the cast configuration
     *
     * @return array The cast configuration or empty array if null
     */
    public function getCast(): array
    {
        return ($this->cast ?? []);

    }//end getCast()


    /**
     * Mapping constructor.
     * Initializes the field types for the Mapping entity.
     *
     * @return void
     */
    public function __construct()
    {
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

    }//end __construct()


    /**
     * Get the JSON fields of the Mapping entity.
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
     * Get the date and time when the mapping was last updated.
     *
     * @return DateTime|null The date and time of the last modification or null if not set
     */
    public function getUpdated(): ?DateTime
    {
        return $this->dateModified;

    }//end getUpdated()


    /**
     * Hydrate the Mapping entity with data from an array.
     *
     * @param array $object The array containing the data to hydrate the entity
     *
     * @return self Returns the hydrated Mapping entity
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
     * Serialize the Mapping entity to JSON.
     *
     * @return array An array representation of the Mapping entity for JSON serialization
     */
    public function jsonSerialize(): array
    {
        $dateCreated = null;
        if (isset($this->dateCreated) === true) {
            $dateCreated = $this->dateCreated->format('c');
        }

        $dateModified = null;
        if (isset($this->dateModified) === true) {
            $dateModified = $this->dateModified->format('c');
        }

        return [
            'id'           => $this->id,
            'uuid'         => $this->uuid,
            'name'         => $this->name,
            'description'  => $this->description,
            'version'      => $this->version,
            'reference'    => $this->reference,
            'mapping'      => $this->mapping,
            'unset'        => $this->unset,
            'cast'         => $this->cast,
            'passThrough'  => $this->passThrough,
            'dateCreated'  => $dateCreated,
            'dateModified' => $dateModified,
        ];

    }//end jsonSerialize()


}//end class
