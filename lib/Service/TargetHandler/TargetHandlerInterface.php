<?php

/**
 * Interface for target handlers that process synchronization data to various target types.
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

use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationContract;

/**
 * Interface for target handlers.
 *
 * Each handler is responsible for processing data to a specific target type.
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
interface TargetHandlerInterface
{


    /**
     * Checks if this handler can handle the given target type.
     *
     * @param string $targetType The type of target to check
     *
     * @return bool True if this handler can handle the target type
     *
     * @psalm-pure
     * @phpstan-return bool
     */
    public function canHandle(string $targetType): bool;


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
     * @psalm-param 'save'|'delete' $action
     */
    public function updateTarget(
        SynchronizationContract $contract,
        Synchronization $synchronization,
        array $targetObject,
        string $action
    ): SynchronizationContract;


    /**
     * Deletes invalid objects from the target system.
     *
     * @param Synchronization    $synchronization       The synchronization entity
     * @param array<string>|null $synchronizedTargetIds Valid target IDs that should not be deleted
     *
     * @return int Number of deleted objects
     */
    public function deleteInvalidObjects(
        Synchronization $synchronization,
        ?array $synchronizedTargetIds=[]
    ): int;


}//end interface
