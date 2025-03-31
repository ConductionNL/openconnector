<?php

/**
 * Abstract base class for target handlers.
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

use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Service\CallService;
use Psr\Container\ContainerInterface;

/**
 * Abstract base class for target handlers with common functionality.
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
abstract class AbstractTargetHandler implements TargetHandlerInterface
{

    /**
     * The call service for making API requests.
     *
     * @var CallService
     */
    protected readonly CallService $callService;

    /**
     * The source mapper for accessing source configuration.
     *
     * @var SourceMapper
     */
    protected readonly SourceMapper $sourceMapper;

    /**
     * The synchronization contract mapper for managing contracts.
     *
     * @var SynchronizationContractMapper
     */
    protected readonly SynchronizationContractMapper $synchronizationContractMapper;

    /**
     * Container interface for accessing other services.
     *
     * @var ContainerInterface
     */
    protected readonly ContainerInterface $container;


    /**
     * Constructor.
     *
     * @param CallService                   $callService                   Service for making API calls
     * @param SourceMapper                  $sourceMapper                  Mapper for source entities
     * @param SynchronizationContractMapper $synchronizationContractMapper Mapper for contract entities
     * @param ContainerInterface            $container                     Container for service access
     */
    public function __construct(
        CallService $callService,
        SourceMapper $sourceMapper,
        SynchronizationContractMapper $synchronizationContractMapper,
        ContainerInterface $container
    ) {
        $this->callService  = $callService;
        $this->sourceMapper = $sourceMapper;
        $this->synchronizationContractMapper = $synchronizationContractMapper;
        $this->container = $container;

    }//end __construct()


    /**
     * Check if an array is associative.
     *
     * @param array<string|int,mixed> $array The array to check
     *
     * @return bool True if associative, false otherwise
     *
     * @psalm-pure
     */
    protected function isAssociativeArray(array $array): bool
    {
        return count(array_filter(array_keys($array), 'is_string')) > 0;

    }//end isAssociativeArray()


}//end class
