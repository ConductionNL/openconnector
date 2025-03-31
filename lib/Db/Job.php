<?php
/**
 * OpenConnector Job Entity
 *
 * This file contains the entity class for job data in the OpenConnector
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
 * Class Job
 *
 * A job represents a scheduled task that will be executed at regular intervals
 * or at specific times. Jobs are used to perform background operations in the
 * OpenConnector application.
 */
class Job extends Entity implements JsonSerializable
{

    /**
     * The unique identifier of the job
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * The name of the job
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The description of the job
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * The reference of the job
     *
     * @var string|null
     */
    protected ?string $reference = null;

    /**
     * The version of the job
     *
     * @var string|null
     */
    protected ?string $version = '0.0.0';

    /**
     * The class responsible for executing the job
     *
     * @var string|null
     */
    protected ?string $jobClass = 'OCA\OpenConnector\Action\PingAction';

    /**
     * The arguments to be passed to the job
     *
     * @var array|null
     */
    protected ?array $arguments = null;

    /**
     * The interval in seconds between job executions
     *
     * @var integer|null
     */
    protected ?int $interval = 3600;

    /**
     * The maximum execution time in seconds
     *
     * @var integer|null
     */
    protected ?int $executionTime = 3600;

    /**
     * Indicates if the job is time-sensitive and should be executed even under heavy load
     *
     * @var boolean|null
     */
    protected ?bool $timeSensitive = true;

    /**
     * Indicates if the job can be executed in parallel
     *
     * @var boolean|null
     */
    protected ?bool $allowParallelRuns = false;

    /**
     * Indicates if the job is enabled
     *
     * @var boolean|null
     */
    protected ?bool $isEnabled = true;

    /**
     * Indicates if the job will only run once and then disable itself
     *
     * @var boolean|null
     */
    protected ?bool $singleRun = false;

    /**
     * The date and time after which the job should be executed
     *
     * @var DateTime|null
     */
    protected ?DateTime $scheduleAfter = null;

    /**
     * The user for whom the job is running, for security reasons
     *
     * @var string|null
     */
    protected ?string $userId = null;

    /**
     * The ID of the job in the job list
     *
     * @var string|null
     */
    protected ?string $jobListId = null;

    /**
     * The duration in seconds to retain all logs
     *
     * @var integer|null
     */
    protected ?int $logRetention = 3600;

    /**
     * The duration in seconds to retain error logs
     *
     * @var integer|null
     */
    protected ?int $errorRetention = 86400;

    /**
     * The last time the job was run
     *
     * @var DateTime|null
     */
    protected ?DateTime $lastRun = null;

    /**
     * The next scheduled time for the job to run
     *
     * @var DateTime|null
     */
    protected ?DateTime $nextRun = null;

    /**
     * The date and time the job was created
     *
     * @var DateTime|null
     */
    protected ?DateTime $created = null;

    /**
     * The date and time the job was last updated
     *
     * @var DateTime|null
     */
    protected ?DateTime $updated = null;


    /**
     * Get the job arguments
     *
     * @return array The job arguments or empty array if null
     */
    public function getArguments(): array
    {
        return ($this->arguments ?? []);

    }//end getArguments()


    /**
     * Job constructor.
     * Initializes the field types for the Job entity.
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
        $this->addType('jobClass', 'string');
        $this->addType('arguments', 'json');
        $this->addType('interval', 'integer');
        $this->addType('executionTime', 'integer');
        $this->addType('timeSensitive', 'boolean');
        $this->addType('allowParallelRuns', 'boolean');
        $this->addType('isEnabled', 'boolean');
        $this->addType('singleRun', 'boolean');
        $this->addType('scheduleAfter', 'datetime');
        $this->addType('userId', 'string');
        $this->addType('jobListId', 'string');
        $this->addType('logRetention', 'integer');
        $this->addType('errorRetention', 'integer');
        $this->addType('lastRun', 'datetime');
        $this->addType('nextRun', 'datetime');
        $this->addType('created', 'datetime');
        $this->addType('updated', 'datetime');

    }//end __construct()


    /**
     * Get the JSON fields of the Job entity.
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
     * Hydrate the Job entity with data from an array.
     *
     * @param array $object The array containing the data to hydrate the entity
     *
     * @return self Returns the hydrated Job entity
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
     * Serialize the Job entity to JSON.
     *
     * @return array An array representation of the Job entity for JSON serialization
     */
    public function jsonSerialize(): array
    {
        $scheduleAfter = null;
        if (isset($this->scheduleAfter) === true) {
            $scheduleAfter = $this->scheduleAfter->format('c');
        }

        $lastRun = null;
        if (isset($this->lastRun) === true) {
            $lastRun = $this->lastRun->format('c');
        }

        $nextRun = null;
        if (isset($this->nextRun) === true) {
            $nextRun = $this->nextRun->format('c');
        }

        $created = null;
        if (isset($this->created) === true) {
            $created = $this->created->format('c');
        }

        $updated = null;
        if (isset($this->updated) === true) {
            $updated = $this->updated->format('c');
        }

        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'name'              => $this->name,
            'description'       => $this->description,
            'reference'         => $this->reference,
            'version'           => $this->version,
            'jobClass'          => $this->jobClass,
            'arguments'         => $this->arguments,
            'interval'          => $this->interval,
            'executionTime'     => $this->executionTime,
            'timeSensitive'     => $this->timeSensitive,
            'allowParallelRuns' => $this->allowParallelRuns,
            'isEnabled'         => $this->isEnabled,
            'singleRun'         => $this->singleRun,
            'scheduleAfter'     => $scheduleAfter,
            'userId'            => $this->userId,
            'jobListId'         => $this->jobListId,
            'logRetention'      => $this->logRetention,
            'errorRetention'    => $this->errorRetention,
            'lastRun'           => $lastRun,
            'nextRun'           => $nextRun,
            'created'           => $created,
            'updated'           => $updated,
        ];

    }//end jsonSerialize()


}//end class
