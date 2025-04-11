<?php

namespace OCA\OpenConnector\Db;

use DateTime;
use JsonSerializable;
use OCP\AppFramework\Db\Entity;

/**
 * Class Rule
 *
 * Represents a rule that can be triggered during endpoint handling
 *
 * @package OCA\OpenConnector\Db
 */
class Rule extends Entity implements JsonSerializable
{

    protected ?string $uuid = null;

    protected ?string $name = null;

    protected ?string $description = null;

    protected ?string $reference = null;

    protected ?string $version = '0.0.0';

    // create, read, update, delete
    protected ?string $action = null;

    // before or after
    protected ?string $timing = 'before';

    // JSON Logic format conditions
    protected ?array $conditions = [];

    // mapping, error, script, synchronization
    protected ?string $type = null;

    // Type-specific configuration
    protected ?array $configuration = [];

    // Order in which the rule should be applied
    protected int $order = 0;

    protected ?DateTime $created = null;

    protected ?DateTime $updated = null;


    /**
     * Get the conditions array
     *
     * @return array The conditions in JSON Logic format or empty array if null
     */
    public function getConditions(): array
    {
        return ($this->conditions ?? []);

    }//end getConditions()


    /**
     * Get the configuration array
     *
     * @return array The type-specific configuration or empty array if null
     */
    public function getConfiguration(): array
    {
        return ($this->configuration ?? []);

    }//end getConfiguration()


    public function __construct()
    {
        $this->addType('uuid', 'string');
        $this->addType('name', 'string');
        $this->addType('description', 'string');
        $this->addType(fieldName:'reference', type: 'string');
        $this->addType(fieldName:'version', type: 'string');
        $this->addType('action', 'string');
        $this->addType('timing', 'string');
        $this->addType('conditions', 'json');
        $this->addType('type', 'string');
        $this->addType('configuration', 'json');
        $this->addType('order', 'integer');
        $this->addType('created', 'datetime');
        $this->addType('updated', 'datetime');

    }//end __construct()


    /**
     * Get fields that should be JSON encoded
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
     * Hydrate the entity from an array of data
     *
     * @param  array<string,mixed> $object Data to hydrate from
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
                // Silent fail if property doesn't exist
            }
        }

        return $this;

    }//end hydrate()


    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'name'          => $this->name,
            'description'   => $this->description,
            'reference'     => $this->reference,
            'version'       => $this->version,
            'action'        => $this->action,
            'timing'        => $this->timing,
            'conditions'    => $this->conditions,
            'type'          => $this->type,
            'configuration' => $this->configuration,
            'order'         => $this->order,
            'created'       => isset($this->created) ? $this->created->format('c') : null,
            'updated'       => isset($this->updated) ? $this->updated->format('c') : null,
        ];

    }//end jsonSerialize()


}//end class
