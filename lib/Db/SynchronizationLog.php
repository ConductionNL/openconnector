<?php
/**
 * OpenConnector SynchronizationLog Entity
 *
 * This file contains the entity class for synchronization log data in the OpenConnector
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
 * Class SynchronizationLog
 *
 * Entity class representing a synchronization log entry.
 *
 * @package OCA\OpenConnector\Db
 */
class SynchronizationLog extends Entity implements JsonSerializable
{

    /**
     * The unique identifier of the synchronization log entry.
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * The message associated with the synchronization log entry.
     *
     * @var string|null
     */
    protected ?string $message = null;

    /**
     * The ID of the synchronization associated with this log entry.
     *
     * @var string|null
     */
    protected ?string $synchronizationId = null;

    /**
     * The result data of the synchronization.
     *
     * @var array|null
     */
    protected ?array $result = [];

    /**
     * The ID of the user who initiated the synchronization.
     *
     * @var string|null
     */
    protected ?string $userId = null;

    /**
     * The session ID associated with the synchronization.
     *
     * @var string|null
     */
    protected ?string $sessionId = null;

    /**
     * Indicates if the synchronization was a test run.
     *
     * @var boolean
     */
    protected bool $test = false;

    /**
     * Indicates if the synchronization was forced.
     *
     * @var boolean
     */
    protected bool $force = false;

    /**
     * The execution time of the synchronization in seconds.
     *
     * @var integer
     */
    protected int $executionTime = 0;

    /**
     * The date and time the synchronization log entry was created.
     *
     * @var DateTime|null
     */
    protected ?DateTime $created = null;

    /**
     * The date and time the synchronization log entry expires.
     *
     * @var DateTime|null
     */
    protected ?DateTime $expires = null;


    /**
     * Get the synchronization result.
     *
     * @return array The result data or empty array if null
     */
    public function getResult(): array
    {
        return ($this->result ?? []);

    }//end getResult()


    /**
     * SynchronizationLog constructor.
     * Initializes the field types for the SynchronizationLog entity.
     *
     * @return void
     */
    public function __construct()
    {
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

    }//end __construct()


    /**
     * Get the JSON fields of the SynchronizationLog entity.
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
     * Hydrate the SynchronizationLog entity with data from an array.
     *
     * @param array $object The array containing the data to hydrate the entity
     *
     * @return self Returns the hydrated SynchronizationLog entity
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
     * Serialize the SynchronizationLog entity to JSON.
     *
     * @return array An array representation of the SynchronizationLog entity for JSON serialization
     */
    public function jsonSerialize(): array
    {
        $created = null;
        if (isset($this->created) === true) {
            $created = $this->created->format('c');
        }

        $expires = null;
        if (isset($this->expires) === true) {
            $expires = $this->expires->format('c');
        }

        return [
            'id'                => $this->id,
            'uuid'              => $this->uuid,
            'message'           => $this->message,
            'synchronizationId' => $this->synchronizationId,
            'result'            => $this->result,
            'userId'            => $this->userId,
            'sessionId'         => $this->sessionId,
            'test'              => $this->test,
            'force'             => $this->force,
            'executionTime'     => $this->executionTime,
            'created'           => $created,
            'expires'           => $expires,
        ];

    }//end jsonSerialize()


}//end class
