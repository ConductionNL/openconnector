<?php
/**
 * OpenConnector - Connect your Nextcloud to external services
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
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
 * Represents an API endpoint configuration entity
 */
class Endpoint extends Entity implements JsonSerializable
{

    /**
     * Unique identifier for the endpoint.
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * The name of the endpoint.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The description of the endpoint.
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * The reference of the endpoint.
     *
     * @var string|null
     */
    protected ?string $reference = null;

    /**
     * The version of the endpoint.
     *
     * @var string|null
     */
    protected ?string $version = '0.0.0';

    /**
     * The actual endpoint, e.g., /api/buildings/{{id}}. An endpoint may contain parameters, e.g., {{id}}.
     *
     * @var string|null
     */
    protected ?string $endpoint = null;

    /**
     * An array representation of the endpoint. Automatically generated.
     *
     * @var         array|null
     * @psalm-var   array<int, string>|null
     * @phpstan-var array<int, string>|null
     */
    protected ?array $endpointArray = [];

    /**
     * A regex representation of the endpoint. Automatically generated.
     *
     * @var string|null
     */
    protected ?string $endpointRegex = null;

    /**
     * HTTP method for the endpoint. One of GET, POST, PUT, DELETE, PATCH, OPTIONS, HEAD.
     * Method and endpoint combination should be unique.
     *
     * @var string|null
     */
    protected ?string $method = null;

    /**
     * The target to attach this endpoint to. Should be one of source (to create a proxy endpoint),
     * register/schema (to create an object endpoint), job (to fire an event), or synchronization
     * (to create a synchronization endpoint).
     *
     * @var string|null
     */
    protected ?string $targetType = null;

    /**
     * The target id to attach this endpoint to.
     *
     * @var string|null
     */
    protected ?string $targetId = null;

    /**
     * Array of conditions to be applied.
     *
     * @var         array|null
     * @psalm-var   array<string, mixed>|null
     * @phpstan-var array<string, mixed>|null
     */
    protected ?array $conditions = [];

    /**
     * Creation timestamp of the endpoint.
     *
     * @var DateTime|null
     */
    protected ?DateTime $created = null;

    /**
     * Last update timestamp of the endpoint.
     *
     * @var DateTime|null
     */
    protected ?DateTime $updated = null;

    /**
     * Input mapping configuration for the endpoint.
     *
     * @var string|null
     */
    protected ?string $inputMapping = null;

    /**
     * Output mapping configuration for the endpoint.
     *
     * @var string|null
     */
    protected ?string $outputMapping = null;

    /**
     * Array of rules to be applied.
     *
     * @var         array|null
     * @psalm-var   array<string, mixed>|null
     * @phpstan-var array<string, mixed>|null
     */
    protected ?array $rules = [];


    /**
     * Constructor to initialize field types
     *
     * @return void
     */
    public function __construct()
    {
        $this->addType(fieldName: 'uuid', type: 'string');
        $this->addType(fieldName: 'name', type: 'string');
        $this->addType(fieldName: 'description', type: 'string');
        $this->addType(fieldName: 'reference', type: 'string');
        $this->addType(fieldName: 'version', type: 'string');
        $this->addType(fieldName: 'endpoint', type: 'string');
        $this->addType(fieldName: 'endpointArray', type: 'json');
        $this->addType(fieldName: 'endpointRegex', type: 'string');
        $this->addType(fieldName: 'method', type: 'string');
        $this->addType(fieldName: 'targetType', type: 'string');
        $this->addType(fieldName: 'targetId', type: 'string');
        $this->addType(fieldName: 'conditions', type: 'json');
        $this->addType(fieldName: 'created', type: 'datetime');
        $this->addType(fieldName: 'updated', type: 'datetime');
        $this->addType(fieldName: 'inputMapping', type: 'string');
        $this->addType(fieldName: 'outputMapping', type: 'string');
        $this->addType(fieldName: 'rules', type: 'json');

    }//end __construct()


    /**
     * Get the endpoint array representation
     *
     * @return         array The endpoint array or empty array if null
     * @psalm-return   array<int, string>
     * @phpstan-return array<int, string>
     */
    public function getEndpointArray(): array
    {
        return ($this->endpointArray ?? []);

    }//end getEndpointArray()


    /**
     * Get the conditions array
     *
     * @return         array The conditions or empty array if null
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getConditions(): array
    {
        return ($this->conditions ?? []);

    }//end getConditions()


    /**
     * Get the rules array
     *
     * @return         array The rules or empty array if null
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getRules(): array
    {
        return ($this->rules ?? []);

    }//end getRules()


    /**
     * Get the field names that are stored as JSON
     *
     * @return         array<int, string> List of JSON field names
     * @psalm-return   array<int, string>
     * @phpstan-return array<int, string>
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
     * Hydrate the entity with data from an array
     *
     * @param         array $object Data to hydrate the entity with
     * @psalm-param   array<string, mixed> $object
     * @phpstan-param array<string, mixed> $object
     * @return        self The hydrated entity
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
                // ("Error writing $key");
            }
        }

        return $this;

    }//end hydrate()


    /**
     * Serialize the endpoint entity to JSON
     *
     * @return array<string,mixed> The serialized endpoint data
     */
    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'uuid'          => $this->uuid,
            'name'          => $this->name,
            'description'   => $this->description,
            'reference'     => $this->reference,
            'version'       => $this->version,
            'endpoint'      => $this->endpoint,
            'endpointArray' => $this->endpointArray,
            'endpointRegex' => $this->endpointRegex,
            'method'        => $this->method,
            'targetType'    => $this->targetType,
            'targetId'      => $this->targetId,
            'conditions'    => $this->conditions,
            'inputMapping'  => $this->inputMapping,
            'outputMapping' => $this->outputMapping,
            'rules'         => $this->rules,
            'created'       => isset($this->created) ? $this->created->format('c') : null,
            'updated'       => isset($this->updated) ? $this->updated->format('c') : null,
        ];

    }//end jsonSerialize()


}//end class
