<?php

/**
 * Source Handler Registry to manage all source handlers.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (C) 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Initial creation of the SourceHandlerRegistry class
 */

namespace OCA\OpenConnector\Service\SourceHandler;

use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Service\CallService;
use Exception;

/**
 * Registry for source handlers.
 *
 * This class maintains a registry of all available source handlers and provides
 * methods to get the appropriate handler for a given source type.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (C) 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Initial creation of the SourceHandlerRegistry class
 */
class SourceHandlerRegistry
{

    /**
     * Array of registered source handlers.
     *
     * @var SourceHandlerInterface[]
     */
    private array $handlers = [];


    /**
     * Constructor.
     *
     * @param CallService $callService The service for making HTTP calls
     */
    public function __construct(CallService $callService)
    {
        // Register default handlers
        $this->registerHandler(new JsonApiHandler($callService));
        $this->registerHandler(new XmlHandler($callService));
        $this->registerHandler(new SoapHandler($callService));

    }//end __construct()


    /**
     * Registers a source handler.
     *
     * @param SourceHandlerInterface $handler The handler to register
     *
     * @return void
     */
    public function registerHandler(SourceHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;

    }//end registerHandler()


    /**
     * Gets the appropriate handler for a source type.
     *
     * @param string $sourceType The type of source
     *
     * @return SourceHandlerInterface The appropriate handler
     *
     * @throws Exception If no suitable handler is found
     *
     * @psalm-suppress MixedReturnStatement
     * @phpstan-return SourceHandlerInterface
     */
    public function getHandler(string $sourceType): SourceHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canHandle($sourceType)) {
                return $handler;
            }
        }

        throw new Exception("No suitable handler found for source type: $sourceType");

    }//end getHandler()


    /**
     * Gets all objects from a source using the appropriate handler.
     *
     * @param Source $source      The source to fetch from
     * @param array  $config      Configuration for the source
     * @param bool   $isTest      Whether this is a test run
     * @param int    $currentPage Current page for pagination
     * @param array  $headers     Optional headers for the request
     * @param array  $query       Optional query parameters
     *
     * @return array Array of objects fetched from the source
     *
     * @throws Exception If there is an error fetching the objects
     *
     * @phpstan-param  array<string, mixed> $config
     * @phpstan-param  array<string, string> $headers
     * @phpstan-param  array<string, mixed> $query
     * @phpstan-return array<mixed>
     */
    public function getAllObjects(
        Source $source,
        array $config,
        bool $isTest=false,
        int $currentPage=1,
        array $headers=[],
        array $query=[]
    ): array {
        $handler = $this->getHandler($source->getType());

        return $handler->getAllObjects(
            source: $source,
            config: $config,
            isTest: $isTest,
            currentPage: $currentPage,
            headers: $headers,
            query: $query
        );

    }//end getAllObjects()


    /**
     * Gets a single object from a source using the appropriate handler.
     *
     * @param Source $source   The source to fetch from
     * @param string $endpoint The endpoint to fetch from
     * @param array  $config   Configuration for the source
     * @param array  $headers  Optional headers for the request
     * @param array  $query    Optional query parameters
     *
     * @return array The fetched object
     *
     * @throws Exception If there is an error fetching the object
     *
     * @phpstan-param  array<string, mixed> $config
     * @phpstan-param  array<string, string> $headers
     * @phpstan-param  array<string, mixed> $query
     * @phpstan-return array<mixed>
     */
    public function getObject(
        Source $source,
        string $endpoint,
        array $config,
        array $headers=[],
        array $query=[]
    ): array {
        $handler = $this->getHandler($source->getType());

        return $handler->getObject(
            source: $source,
            endpoint: $endpoint,
            config: $config,
            headers: $headers,
            query: $query
        );

    }//end getObject()


}//end class
