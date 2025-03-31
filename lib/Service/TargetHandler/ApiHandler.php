<?php

/**
 * Handler for API target operations.
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

/**
 * Handler for API target operations.
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
class ApiHandler extends AbstractTargetHandler
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
    public function canHandle(string $targetType): bool
    {
        return $targetType === 'api';

    }//end canHandle()


    /**
     * Updates or creates an object in the API target system.
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
    ): SynchronizationContract {
        $target       = $this->sourceMapper->find(id: $synchronization->getTargetId());
        $targetConfig = $synchronization->getTargetConfig();
        $endpoint     = ($targetConfig['endpoint'] ?? '');

        // Clean endpoint if it contains target location
        if (str_starts_with($endpoint, $target->getLocation()) === true) {
            $endpoint = str_replace(search: $target->getLocation(), replace: '', subject: $endpoint);
        }

        // Handle delete action
        if ($action === 'delete') {
            if ($contract->getTargetId() === null) {
                // Nothing to delete
                return $contract;
            }

            $deleteEndpoint = $endpoint.'/'.$contract->getTargetId();
            $response       = $this->callService->call(
                source: $target,
                endpoint: $deleteEndpoint,
                method: 'DELETE',
                config: $targetConfig
            )->getResponse();

            $contract->setTargetHash(md5(serialize($response['body'])));
            $contract->setTargetId(null);
            $contract->setTargetLastAction('delete');

            return $contract;
        }

        // Handle create/update action
        $targetConfig['json'] = $targetObject;

        if ($contract->getTargetId() === null) {
            // Create new object
            $response = $this->callService->call(
                source: $target,
                endpoint: $endpoint,
                method: 'POST',
                config: $targetConfig
            )->getResponse();

            $body    = json_decode($response['body'], true);
            $idField = ($targetConfig['idposition'] ?? 'id');

            $contract->setTargetId(($body[$idField] ?? $body['id']));
            $contract->setTargetHash(md5(serialize($body)));
            $contract->setTargetLastAction('create');
        } else {
            // Update existing object
            $updateEndpoint = $endpoint.'/'.$contract->getTargetId();
            $response       = $this->callService->call(
                source: $target,
                endpoint: $updateEndpoint,
                method: 'PUT',
                config: $targetConfig
            )->getResponse();

            $body = json_decode($response['body'], true);
            $contract->setTargetHash(md5(serialize($body)));
            $contract->setTargetLastAction('update');
        }//end if

        return $contract;

    }//end updateTarget()


    /**
     * Deletes invalid objects from the target system.
     *
     * @param Synchronization    $synchronization       The synchronization entity
     * @param array<string>|null $synchronizedTargetIds Valid target IDs that should not be deleted
     *
     * @return int Number of deleted objects
     *
     * @throws Exception If deletion fails
     */
    public function deleteInvalidObjects(
        Synchronization $synchronization,
        ?array $synchronizedTargetIds=[]
    ): int {
        $deletedObjectsCount = 0;
        $target       = $this->sourceMapper->find(id: $synchronization->getTargetId());
        $targetConfig = $synchronization->getTargetConfig();

        // Get all contracts for this synchronization
        $allContracts = $this->synchronizationContractMapper->findAllBySynchronization(
            synchronizationId: $synchronization->getId()
        );

        $allContractTargetIds = [];
        foreach ($allContracts as $contract) {
            if ($contract->getTargetId() !== null) {
                $allContractTargetIds[] = $contract->getTargetId();
            }
        }

        // Initialize $synchronizedTargetIds as empty array if null
        if ($synchronizedTargetIds === null) {
            $synchronizedTargetIds = [];
        }

        // Check if we have contracts that became invalid or do not exist in the source anymore
        $targetIdsToDelete = array_diff($allContractTargetIds, $synchronizedTargetIds);

        foreach ($targetIdsToDelete as $targetIdToDelete) {
            try {
                $synchronizationContract = $this->synchronizationContractMapper->findOnTarget(
                    synchronization: $synchronization->getId(),
                    targetId: $targetIdToDelete
                );

                if ($synchronizationContract === null) {
                    continue;
                }

                $synchronizationContract = $this->updateTarget(
                    contract: $synchronizationContract,
                    synchronization: $synchronization,
                    targetObject: [],
                    action: 'delete'
                );

                $this->synchronizationContractMapper->update($synchronizationContract);
                $deletedObjectsCount++;
            } catch (Exception $exception) {
                // @todo log error
            }//end try
        }//end foreach

        return $deletedObjectsCount;

    }//end deleteInvalidObjects()


}//end class
