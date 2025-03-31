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
 * Class Synchronization
 *
 * Represents a synchronization configuration between a source and target
 */
class Synchronization extends Entity implements JsonSerializable
{

    /**
     * The unique identifier of the synchronization.
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * The name of the synchronization.
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The description of the synchronization.
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
     * The version of the synchronization.
     *
     * @var string|null
     */
    protected ?string $version = '0.0.0';

    // Source

    /**
     * The ID of the source object.
     *
     * @var string|null
     */
    protected ?string $sourceId = null;

    /**
     * The type of the source object (e.g., API, database, register/schema).
     *
     * @var string|null
     */
    protected ?string $sourceType = null;

    /**
     * The hash of the source object when it was last synced.
     *
     * @var string|null
     */
    protected ?string $sourceHash = null;

    /**
     * The mapping ID of the mapping that we map the object to for hashing.
     *
     * @var string|null
     */
    protected ?string $sourceHashMapping = null;

    /**
     * The mapping of the source object to the target object.
     *
     * @var string|null
     */
    protected ?string $sourceTargetMapping = null;

    /**
     * The configuration of the object in the source.
     *
     * @var         array|null
     * @psalm-var   array<string, mixed>|null
     * @phpstan-var array<string, mixed>|null
     */
    protected ?array $sourceConfig = [];

    /**
     * The last changed date of the source object.
     *
     * @var DateTime|null
     */
    protected ?DateTime $sourceLastChanged = null;

    /**
     * The last checked date of the source object.
     *
     * @var DateTime|null
     */
    protected ?DateTime $sourceLastChecked = null;

    /**
     * The last synced date of the source object.
     *
     * @var DateTime|null
     */
    protected ?DateTime $sourceLastSynced = null;

    /**
     * The last page synced. Used for keeping track of where to continue syncing after the rate limit has been exceeded on the source with pagination.
     *
     * @var integer
     */
    protected ?int $currentPage = 1;

    // Target

    /**
     * The ID of the target object.
     *
     * @var string|null
     */
    protected ?string $targetId = null;

    /**
     * The type of the target object (e.g., API, database, register/schema).
     *
     * @var string|null
     */
    protected ?string $targetType = null;

    /**
     * The hash of the target object.
     *
     * @var string|null
     */
    protected ?string $targetHash = null;

    /**
     * The mapping of the target object to the source object.
     *
     * @var string|null
     */
    protected ?string $targetSourceMapping = null;

    /**
     * The configuration of the object in the target.
     *
     * @var         array|null
     * @psalm-var   array<string, mixed>|null
     * @phpstan-var array<string, mixed>|null
     */
    protected ?array $targetConfig = [];

    /**
     * The last changed date of the target object.
     *
     * @var DateTime|null
     */
    protected ?DateTime $targetLastChanged = null;

    /**
     * The last checked date of the target object.
     *
     * @var DateTime|null
     */
    protected ?DateTime $targetLastChecked = null;

    /**
     * The last synced date of the target object.
     *
     * @var DateTime|null
     */
    protected ?DateTime $targetLastSynced = null;

    // General

    /**
     * The date and time the synchronization was created.
     *
     * @var DateTime|null
     */
    protected ?DateTime $created = null;

    /**
     * The date and time the synchronization was updated.
     *
     * @var DateTime|null
     */
    protected ?DateTime $updated = null;

    /**
     * The conditions for synchronization.
     *
     * @var         array
     * @psalm-var   array<string, mixed>
     * @phpstan-var array<string, mixed>
     */
    protected array $conditions = [];

    /**
     * The follow-up actions after synchronization.
     *
     * @var         array
     * @psalm-var   array<string, mixed>
     * @phpstan-var array<string, mixed>
     */
    protected array $followUps = [];

    /**
     * The actions to be performed during synchronization.
     *
     * @var         array
     * @psalm-var   array<string, mixed>
     * @phpstan-var array<string, mixed>
     */
    protected array $actions = [];


    /**
     * Constructor to initialize field types
     *
     * @return void
     */
    public function __construct()
    {
        $this->addType('uuid', 'string');
        $this->addType('name', 'string');
        $this->addType('description', 'string');
        $this->addType(fieldName: 'reference', type: 'string');
        $this->addType('version', 'string');
        $this->addType('sourceId', 'string');
        $this->addType('sourceType', 'string');
        $this->addType('sourceHash', 'string');
        $this->addType('sourceHashMapping', 'string');
        $this->addType('sourceTargetMapping', 'string');
        $this->addType('sourceConfig', 'json');
        $this->addType('sourceLastChanged', 'datetime');
        $this->addType('sourceLastChecked', 'datetime');
        $this->addType('sourceLastSynced', 'datetime');
        $this->addType('currentPage', 'integer');
        $this->addType('targetId', 'string');
        $this->addType('targetType', 'string');
        $this->addType('targetHash', 'string');
        $this->addType('targetSourceMapping', 'string');
        $this->addType('targetConfig', 'json');
        $this->addType('targetLastChanged', 'datetime');
        $this->addType('targetLastChecked', 'datetime');
        $this->addType('targetLastSynced', 'datetime');
        $this->addType('created', 'datetime');
        $this->addType('updated', 'datetime');
        $this->addType(fieldName: 'conditions', type: 'json');
        $this->addType(fieldName: 'followUps', type: 'json');
        $this->addType(fieldName: 'actions', type: 'json');

    }//end __construct()


    /**
     * Get the source configuration array
     *
     * @return         array The source configuration or empty array if null
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getSourceConfig(): array
    {
        return ($this->sourceConfig ?? []);

    }//end getSourceConfig()


    /**
     * Get the target configuration array
     *
     * @return         array The target configuration or empty array if null
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getTargetConfig(): array
    {
        return ($this->targetConfig ?? []);

    }//end getTargetConfig()


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
     * Get the follow-ups array
     *
     * @return         array The follow-ups or empty array if null
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getFollowUps(): array
    {
        return ($this->followUps ?? []);

    }//end getFollowUps()


    /**
     * Get the actions array
     *
     * @return         array The actions or empty array if null
     * @psalm-return   array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getActions(): array
    {
        return ($this->actions ?? []);

    }//end getActions()


    /**
     * Checks through sourceConfig if the source of this sync uses pagination
     *
     * @return bool true if its uses pagination
     */
    public function usesPagination(): bool
    {
        if (isset($this->sourceConfig['usesPagination']) === true
            && ($this->sourceConfig['usesPagination'] === false
            || $this->sourceConfig['usesPagination'] === 'false')
        ) {
            return false;
        }

        // By default sources use basic pagination.
        return true;

    }//end usesPagination()


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
                // Error handling could be improved here.
            }
        }

        return $this;

    }//end hydrate()


    /**
     * Serialize the synchronization entity to JSON
     *
     * @return array<string, mixed> The serialized synchronization data
     */
    public function jsonSerialize(): array
    {
        return [
            'id'                  => $this->id,
            'uuid'                => $this->uuid,
            'name'                => $this->name,
            'description'         => $this->description,
            'reference'           => $this->reference,
            'version'             => $this->version,
            'sourceId'            => $this->sourceId,
            'sourceType'          => $this->sourceType,
            'sourceHash'          => $this->sourceHash,
            'sourceHashMapping'   => $this->sourceHashMapping,
            'sourceTargetMapping' => $this->sourceTargetMapping,
            'sourceConfig'        => $this->sourceConfig,
            'sourceLastChanged'   => isset($this->sourceLastChanged) === true ? $this->sourceLastChanged->format('c') : null,
            'sourceLastChecked'   => isset($this->sourceLastChecked) === true ? $this->sourceLastChecked->format('c') : null,
            'sourceLastSynced'    => isset($this->sourceLastSynced) === true ? $this->sourceLastSynced->format('c') : null,
            'currentPage'         => $this->currentPage,
            'targetId'            => $this->targetId,
            'targetType'          => $this->targetType,
            'targetHash'          => $this->targetHash,
            'targetSourceMapping' => $this->targetSourceMapping,
            'targetConfig'        => $this->targetConfig,
            'targetLastChanged'   => isset($this->targetLastChanged) === true ? $this->targetLastChanged->format('c') : null,
            'targetLastChecked'   => isset($this->targetLastChecked) === true ? $this->targetLastChecked->format('c') : null,
            'targetLastSynced'    => isset($this->targetLastSynced) === true ? $this->targetLastSynced->format('c') : null,
            'created'             => isset($this->created) === true ? $this->created->format('c') : null,
            'updated'             => isset($this->updated) === true ? $this->updated->format('c') : null,
            'conditions'          => $this->conditions,
            'followUps'           => $this->followUps,
            'actions'             => $this->actions,
        ];

    }//end jsonSerialize()


}//end class
