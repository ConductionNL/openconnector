<?php
/**
 * OpenConnector Consumer Entity
 *
 * This file contains the entity class for consumer data in the OpenConnector
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
 * Class Consumer
 *
 * A consumer is a service or application that consumes events, has access to endpoints and mappings,
 * and is able to trigger actions based on the events. It is the main actor in the openconnector platform
 * and determines authentication and authorizations on all aspects of the platform.
 *
 * @package OCA\OpenConnector\Db
 */
class Consumer extends Entity implements JsonSerializable
{

    /**
     * Unique identifier for the consumer
     *
     * @var string|null
     */
    protected ?string $uuid = null;

    /**
     * Name of the consumer
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * Description of the consumer
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * Domains the consumer is allowed to run from
     *
     * @var array|null
     */
    protected ?array $domains = [];

    /**
     * IPs the consumer is allowed to run from
     *
     * @var array|null
     */
    protected ?array $ips = [];

    /**
     * Authorization type of the consumer
     *
     * Should be one of: 'none', 'basic', 'bearer', 'apiKey', 'oauth2', 'jwt'.
     * The consumer needs to be able to handle the authorization type.
     *
     * @var string|null
     */
    protected ?string $authorizationType = null;

    /**
     * Authorization configuration of the consumer
     *
     * @var array|null
     */
    protected ?array $authorizationConfiguration = [];

    /**
     * The date and time the consumer was created
     *
     * @var DateTime|null
     */
    protected ?DateTime $created = null;

    /**
     * The date and time the consumer was updated
     *
     * @var DateTime|null
     */
    protected ?DateTime $updated = null;

    /**
     * The ID of the user who created/owns the consumer
     *
     * @var string|null
     */
    protected ?string $userId = null;


    /**
     * Get the allowed domains
     *
     * @return array The allowed domains or empty array if null
     */
    public function getDomains(): array
    {
        return ($this->domains ?? []);

    }//end getDomains()


    /**
     * Get the allowed IPs
     *
     * @return array The allowed IPs or empty array if null
     */
    public function getIps(): array
    {
        return ($this->ips ?? []);

    }//end getIps()


    /**
     * Get the authorization configuration
     *
     * @return array The authorization configuration or empty array if null
     */
    public function getAuthorizationConfiguration(): array
    {
        return ($this->authorizationConfiguration ?? []);

    }//end getAuthorizationConfiguration()


    /**
     * Consumer constructor.
     * Initializes the field types for the Consumer entity.
     */
    public function __construct()
    {
        $this->addType('uuid', 'string');
        $this->addType('name', 'string');
        $this->addType('description', 'string');
        $this->addType('domains', 'json');
        $this->addType('ips', 'json');
        $this->addType('authorizationType', 'string');
        $this->addType('authorizationConfiguration', 'json');
        $this->addType('created', 'datetime');
        $this->addType('updated', 'datetime');
        $this->addType('userId', 'string');

    }//end __construct()


    /**
     * Get the JSON fields of the Consumer entity.
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
     * Hydrate the Consumer entity with data from an array.
     *
     * @param array $object The array containing the data to hydrate the entity
     *
     * @return self Returns the hydrated Consumer entity
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
     * Serialize the Consumer entity to JSON.
     *
     * @return array An array representation of the Consumer entity for JSON serialization
     */
    public function jsonSerialize(): array
    {
        $created = null;
        if (isset($this->created) === true) {
            $created = $this->created->format('c');
        }

        $updated = null;
        if (isset($this->updated) === true) {
            $updated = $this->updated->format('c');
        }

        return [
            'id'                         => $this->id,
            'uuid'                       => $this->uuid,
            'name'                       => $this->name,
            'description'                => $this->description,
            'domains'                    => $this->domains,
            'ips'                        => $this->ips,
            'authorizationType'          => $this->authorizationType,
            'authorizationConfiguration' => $this->authorizationConfiguration,
            'userId'                     => $this->userId,
            'created'                    => $created,
            'updated'                    => $updated,
        ];

    }//end jsonSerialize()


}//end class
