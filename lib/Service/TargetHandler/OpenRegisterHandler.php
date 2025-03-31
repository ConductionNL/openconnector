<?php

/**
 * Handler for OpenRegister target operations.
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

use DateTime;
use Exception;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenRegister\Service\ObjectService;
use Symfony\Component\Uid\Uuid;

/**
 * Handler for OpenRegister target operations.
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
class OpenRegisterHandler extends AbstractTargetHandler
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
        return $targetType === 'register/schema';

    }//end canHandle()


    /**
     * Updates or deletes a target object in the Open Register system.
     *
     * @param SynchronizationContract $contract        The contract being updated
     * @param Synchronization         $synchronization The synchronization configuration
     * @param array<string,mixed>     $targetObject    The target object data
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
        // Get the OpenRegister ObjectService
        $objectService = $this->container->get(ObjectService::class);
        $sourceConfig  = $this->callService->applyConfigDot($synchronization->getSourceConfig());

        // Set target ID if exists
        if ($contract->getTargetId() !== null) {
            $targetObject['id'] = $contract->getTargetId();
        }

        // Update sub-object IDs if configured
        if (isset($sourceConfig['subObjects']) === true) {
            $targetObject = $this->updateIdsOnSubObjects(
                subObjectsConfig: $sourceConfig['subObjects'],
                synchronizationId: $synchronization->getId(),
                targetObject: $targetObject
            );
        }

        // Parse target ID for register and schema
        [
            $register,
            $schema,
        ] = explode('/', $synchronization->getTargetId());

        // Process based on action type
        switch ($action) {
            case 'save':
                $target = $objectService->saveObject(
                register: $register,
                schema: $schema,
                object: $targetObject
                );

                $contract->setTargetId($target->getUuid());

                // Handle sub-objects if configured
                if (isset($sourceConfig['subObjects']) === true) {
                    $targetObject = $objectService->renderEntity($target->jsonSerialize(), ['all']);
                    $this->updateContractsForSubObjects(
                        subObjectsConfig: $sourceConfig['subObjects'],
                        synchronizationId: $synchronization->getId(),
                        targetObject: $targetObject
                    );
                }

                // Set action based on operation type
                $contract->setTargetLastAction(
                $contract->getTargetId() ? 'update' : 'create'
                );
                break;

            case 'delete':
                $objectService->deleteObject(
                register: $register,
                schema: $schema,
                uuid: $contract->getTargetId()
                );

                $contract->setTargetId(null);
                $contract->setTargetLastAction('delete');
                break;
        }//end switch

        return $contract;

    }//end updateTarget()


    /**
     * Deletes invalid objects associated with a synchronization.
     *
     * @param Synchronization    $synchronization       The synchronization entity
     * @param array<string>|null $synchronizedTargetIds Valid target IDs that should not be deleted
     *
     * @return int Number of deleted objects
     *
     * @throws Exception If an error occurs during deletion
     */
    public function deleteInvalidObjects(
        Synchronization $synchronization,
        ?array $synchronizedTargetIds=[]
    ): int {
        $deletedObjectsCount = 0;

        [
            $registerId,
            $schemaId,
        ] = explode('/', $synchronization->getTargetId());

        $allContracts = $this->synchronizationContractMapper->findAllBySynchronizationAndSchema(
            synchronizationId: $synchronization->getId(),
            schemaId: $schemaId
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


    /**
     * Updates synchronization contracts for sub-objects.
     *
     * @param array<string,mixed> $subObjectsConfig  The sub-objects configuration
     * @param string              $synchronizationId The synchronization ID
     * @param array<string,mixed> $targetObject      The target object with sub-objects
     *
     * @return void
     */
    private function updateContractsForSubObjects(
        array $subObjectsConfig,
        string $synchronizationId,
        array $targetObject
    ): void {
        foreach ($subObjectsConfig as $propertyName => $subObjectConfig) {
            if (isset($targetObject[$propertyName]) === false) {
                continue;
            }

            $propertyData = $targetObject[$propertyName];

            // Process associative array sub-objects
            if (is_array($propertyData) === true && $this->isAssociativeArray($propertyData) === true) {
                if (isset($propertyData['originId'])) {
                    $this->processSyncContract($synchronizationId, $propertyData);
                }

                // Process nested sub-objects
                foreach ($propertyData as $key => $value) {
                    if (is_array($value) === true
                        && isset($subObjectConfig['subObjects']) === true
                    ) {
                        $this->updateContractsForSubObjects(
                            $subObjectConfig['subObjects'],
                            $synchronizationId,
                            [$key => $value]
                        );
                    }
                }
            }

            // Process indexed array of sub-objects
            if (is_array($propertyData) === true && !$this->isAssociativeArray($propertyData)) {
                foreach ($propertyData as $subObjectData) {
                    if (is_array($subObjectData) === true
                        && isset($subObjectData['originId']) === true
                    ) {
                        $this->processSyncContract($synchronizationId, $subObjectData);
                    }

                    // Process nested sub-objects
                    if (is_array($subObjectData) === true
                        && isset($subObjectConfig['subObjects']) === true
                    ) {
                        $this->updateContractsForSubObjects(
                            $subObjectConfig['subObjects'],
                            $synchronizationId,
                            $subObjectData
                        );
                    }
                }
            }
        }//end foreach

    }//end updateContractsForSubObjects()


    /**
     * Process a single synchronization contract for a sub-object.
     *
     * @param string              $synchronizationId The synchronization ID
     * @param array<string,mixed> $subObjectData     The sub-object data
     *
     * @return void
     */
    private function processSyncContract(
        string $synchronizationId,
        array $subObjectData
    ): void {
        // Extract nested ID if exists
        $id = ($subObjectData['id']['id']['id']['id'] ?? $subObjectData['id']['id']['id'] ?? $subObjectData['id']['id'] ?? $subObjectData['id']);

        // Find existing contract or create new one
        $subContract = $this->synchronizationContractMapper->findByOriginId(
            originId: $subObjectData['originId']
        );

        if (!$subContract) {
            $subContract = new SynchronizationContract();
            $subContract->setSynchronizationId($synchronizationId);
            $subContract->setOriginId($subObjectData['originId']);
            $subContract->setTargetId($id);
            $subContract->setUuid(Uuid::v4());
            $subContract->setTargetHash(md5(serialize($subObjectData)));
            $subContract->setTargetLastChanged(new DateTime());
            $subContract->setTargetLastSynced(new DateTime());
            $subContract->setSourceLastSynced(new DateTime());

            $subContract = $this->synchronizationContractMapper->insert($subContract);
        } else {
            $subContract = $this->synchronizationContractMapper->updateFromArray(
                id: $subContract->getId(),
                object: [
                    'synchronizationId' => $synchronizationId,
                    'originId'          => $subObjectData['originId'],
                    'targetId'          => $id,
                    'targetHash'        => md5(serialize($subObjectData)),
                    'targetLastChanged' => new DateTime(),
                    'targetLastSynced'  => new DateTime(),
                    'sourceLastSynced'  => new DateTime(),
                ]
            );
        }//end if

        // Create contract log
        $this->synchronizationContractLogMapper->createFromArray(
            [
                'synchronizationId'         => $subContract->getSynchronizationId(),
                'synchronizationContractId' => $subContract->getId(),
                'target'                    => $subObjectData,
                'expires'                   => new DateTime('+1 day'),
            ]
        );

    }//end processSyncContract()


    /**
     * Processes subObjects update their arrays with existing targetId's so OpenRegister can update the objects instead of duplicate them.
     *
     * @param array<string,mixed> $subObjectsConfig     The configuration for subObjects
     * @param string              $synchronizationId    The ID of the synchronization
     * @param array<string,mixed> $targetObject         The target object containing subObjects to be processed
     * @param bool|null           $parentIsNumericArray Whether the parent object is a numeric array (default false)
     *
     * @return array<string,mixed> The updated target object with IDs updated on subObjects
     */
    private function updateIdsOnSubObjects(
        array $subObjectsConfig,
        string $synchronizationId,
        array $targetObject,
        ?bool $parentIsNumericArray=false
    ): array {
        foreach ($subObjectsConfig as $propertyName => $subObjectConfig) {
            if (isset($targetObject[$propertyName]) === false) {
                continue;
            }

            // If property data is an array of sub-objects, iterate and process
            if (is_array($targetObject[$propertyName]) === true) {
                if (isset($targetObject[$propertyName]['originId']) === true) {
                    $targetObject[$propertyName] = $this->updateIdOnSubObject($synchronizationId, $targetObject[$propertyName]);
                }

                // Recursively process any nested sub-objects within the associative array
                foreach ($targetObject[$propertyName] as $key => $value) {
                    if (is_array($value) === true && isset($subObjectConfig['subObjects'][$key]) === true) {
                        if ($this->isAssociativeArray($value) === true) {
                            $targetObject[$propertyName][$key] = $this->updateIdsOnSubObjects(
                                $subObjectConfig['subObjects'],
                                $synchronizationId,
                                [$key => $value]
                            );
                        } else if (is_array($value) === true && $this->isAssociativeArray(reset($value)) === true) {
                            foreach ($value as $iterativeSubArrayKey => $iterativeSubArray) {
                                $targetObject[$propertyName][$key][$iterativeSubArrayKey] = $this->updateIdsOnSubObjects(
                                    $subObjectConfig['subObjects'],
                                    $synchronizationId,
                                    [$key => $iterativeSubArray],
                                    true
                                );
                            }
                        }
                    }
                }
            }//end if
        }//end foreach

        if ($parentIsNumericArray === true) {
            return reset($targetObject);
        }

        return $targetObject;

    }//end updateIdsOnSubObjects()


    /**
     * Updates the ID of a single subObject based on its synchronization contract so OpenRegister can update the object.
     *
     * @param string              $synchronizationId The ID of the synchronization
     * @param array<string,mixed> $subObject         The subObject to update
     *
     * @return array<string,mixed> The updated subObject with the ID set based on the synchronization contract
     */
    private function updateIdOnSubObject(string $synchronizationId, array $subObject): array
    {
        if (isset($subObject['originId']) === true) {
            $subObjectContract = $this->synchronizationContractMapper->findSyncContractByOriginId(
                synchronizationId: $synchronizationId,
                originId: $subObject['originId']
            );

            if ($subObjectContract !== null) {
                $subObject['id'] = $subObjectContract->getTargetId();
            }
        }

        return $subObject;

    }//end updateIdOnSubObject()


}//end class
