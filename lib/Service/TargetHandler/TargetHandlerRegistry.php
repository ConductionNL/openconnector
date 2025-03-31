<?php

/**
 * Registry for target handlers.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (C) 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0
 */

namespace OCA\OpenConnector\Service\TargetHandler;

use Exception;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationContract;
use Psr\Container\ContainerInterface;

/**
 * Registry for target handlers.
 *
 * This service manages different handler classes for different target types.
 * It automatically selects the appropriate handler based on the target type.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (C) 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0
 */
class TargetHandlerRegistry
{

    /**
     * The registered target handlers.
     *
     * @var TargetHandlerInterface[]
     */
    private array $handlers = [];

    /**
     * Container for service resolution.
     *
     * @var ContainerInterface
     */
    private readonly ContainerInterface $container;


    /**
     * Constructor.
     *
     * @param ContainerInterface $container      Container for service resolution
     * @param array<string>|null $handlerClasses Optional list of handler class names to register
     */
    public function __construct(
        ContainerInterface $container,
        ?array $handlerClasses=null
    ) {
        $this->container = $container;

        // Register handler classes if provided
        if ($handlerClasses !== null) {
            foreach ($handlerClasses as $handlerClass) {
                $this->registerHandler($this->container->get($handlerClass));
            }
        }

    }//end __construct()


    /**
     * Register a new target handler.
     *
     * @param TargetHandlerInterface $handler The handler to register
     *
     * @return self For method chaining
     */
    public function registerHandler(TargetHandlerInterface $handler): self
    {
        $this->handlers[] = $handler;
        return $this;

    }//end registerHandler()


    /**
     * Get the appropriate handler for a target type.
     *
     * @param string $targetType The target type to find a handler for
     *
     * @return TargetHandlerInterface The matching handler
     *
     * @throws Exception If no suitable handler is found
     */
    public function getHandlerForType(string $targetType): TargetHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($targetType)) {
                return $handler;
            }
        }

        throw new Exception("No suitable target handler found for type: $targetType");

    }//end getHandlerForType()


    /**
     * Updates or creates an object in the target system.
     *
     * @param SynchronizationContract $contract        The synchronization contract
     * @param Synchronization         $synchronization The synchronization configuration
     * @param array<string,mixed>     $targetObject    The object data to write
     * @param string                  $action          The action to perform ('save'|'delete')
     *
     * @return SynchronizationContract The updated synchronization contract
     *
     * @throws Exception If no suitable handler is found
     *
     * @psalm-param 'save'|'delete' $action
     */
    public function updateTarget(
        SynchronizationContract $contract,
        Synchronization $synchronization,
        array $targetObject,
        string $action='save'
    ): SynchronizationContract {
        $handler = $this->getHandlerForType($synchronization->getTargetType());

        return $handler->updateTarget(
            contract: $contract,
            synchronization: $synchronization,
            targetObject: $targetObject,
            action: $action
        );

    }//end updateTarget()


    /**
     * Deletes invalid objects from the target system.
     *
     * @param Synchronization    $synchronization       The synchronization entity
     * @param array<string>|null $synchronizedTargetIds Valid target IDs that should not be deleted
     *
     * @return int Number of deleted objects
     *
     * @throws Exception If no suitable handler is found
     */
    public function deleteInvalidObjects(
        Synchronization $synchronization,
        ?array $synchronizedTargetIds=[]
    ): int {
        $handler = $this->getHandlerForType($synchronization->getTargetType());

        return $handler->deleteInvalidObjects(
            synchronization: $synchronization,
            synchronizedTargetIds: $synchronizedTargetIds
        );

    }//end deleteInvalidObjects()


}//end class
