<?php

/**
 * Interface for source handlers that fetch objects from different types of sources.
 *
 * @category  Interface
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (C) 2024 Conduction B.V.
 * @license   EUPL 1.2
 * @version   1.0.0
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Description of when this class was added
 */

namespace OCA\OpenConnector\Service\SourceHandler;

/**
 * Interface for source handlers that fetch objects from different types of sources.
 *
 * @category  Interface
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (C) 2024 Conduction B.V.
 * @license   EUPL 1.2
 * @version   1.0.0
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Description of when this class was added
 */
interface SourceHandlerInterface
{


    /**
     * Fetches all objects from the source.
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
     * @throws \Exception If there is an error fetching the objects
     */
    public function getAllObjects(
        Source $source,
        array $config,
        bool $isTest=false,
        int $currentPage=1,
        array $headers=[],
        array $query=[]
    ): array;


    /**
     * Fetches a single object from the source.
     *
     * @param Source $source   The source to fetch from
     * @param string $endpoint The endpoint to fetch from
     * @param array  $config   Configuration for the source
     * @param array  $headers  Optional headers for the request
     * @param array  $query    Optional query parameters
     *
     * @return array The fetched object
     *
     * @throws \Exception If there is an error fetching the object
     */
    public function getObject(
        Source $source,
        string $endpoint,
        array $config,
        array $headers=[],
        array $query=[]
    ): array;


    /**
     * Checks if this handler can handle the given source type.
     *
     * @param string $sourceType The type of source to check
     *
     * @return bool True if this handler can handle the source type
     */
    public function canHandle(string $sourceType): bool;


}//end interface
