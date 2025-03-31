<?php

/**
 * Handler for JSON API sources.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (C) 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Description of when this class was added
 */

namespace OCA\OpenConnector\Service\SourceHandler;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

/**
 * Handler for JSON API sources.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright Copyright (C) 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Description of when this class was added
 */
class JsonApiHandler extends AbstractSourceHandler
{


    /**
     * Checks if this handler can handle the given source type.
     *
     * @param string $sourceType The type of source to check
     *
     * @return bool True if this handler can handle the source type
     *
     * @psalm-pure
     * @phpstan-return bool
     */
    public function canHandle(string $sourceType): bool
    {
        return $sourceType === 'json-api';

    }//end canHandle()


    /**
     * Gets all objects from a JSON API source.
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
    ): array {
        $this->checkRateLimit($source);

        $endpoint       = ($config['endpoint'] ?? '');
        $usesPagination = $config['usesPagination'] ?? true;

        $requestConfig = [
            'headers' => $headers,
            'query'   => $query,
        ];

        return $this->fetchAllPages(
            source: $source,
            endpoint: $endpoint,
            config: $requestConfig,
            sourceConfig: $config,
            currentPage: $currentPage,
            isTest: $isTest,
            usesPagination: $usesPagination
        );

    }//end getAllObjects()


    /**
     * Gets a single object from a JSON API source.
     *
     * @param Source $source   The source to fetch from
     * @param string $endpoint The endpoint to fetch from
     * @param array  $config   Configuration for the source
     * @param array  $headers  Optional headers for the request
     * @param array  $query    Optional query parameters
     *
     * @return array Array of the fetched object
     *
     * @throws \Exception If there is an error fetching the object
     */
    public function getObject(
        Source $source,
        string $endpoint,
        array $config,
        array $headers=[],
        array $query=[]
    ): array {
        $this->checkRateLimit($source);

        if (str_starts_with($endpoint, $source->getLocation()) === true) {
            $endpoint = str_replace(search: $source->getLocation(), replace: '', subject: $endpoint);
        }

        $requestConfig = [
            'headers' => $headers,
            'query'   => $query,
        ];

        $response = $this->callService->call(
            source: $source,
            endpoint: $endpoint,
            config: $requestConfig,
            read: true
        )->getResponse();

        return json_decode($response['body'], true);

    }//end getObject()


    /**
     * Recursively fetches all pages from a paginated API.
     *
     * @param Source    $source           The source to fetch from
     * @param string    $endpoint         The endpoint to fetch from
     * @param array     $config           Request configuration
     * @param array     $sourceConfig     Source configuration
     * @param int       $currentPage      Current page number
     * @param bool      $isTest           Whether this is a test run
     * @param bool      $usesPagination   Whether to use pagination
     * @param bool|null $usesNextEndpoint Whether to use next endpoint for pagination
     *
     * @return array Array of all objects from all pages
     *
     * @throws GuzzleException
     * @throws TooManyRequestsHttpException
     */
    private function fetchAllPages(
        Source $source,
        string $endpoint,
        array $config,
        array $sourceConfig,
        int $currentPage,
        bool $isTest=false,
        bool $usesPagination=true,
        ?bool $usesNextEndpoint=null
    ): array {
        $callLog  = $this->callService->call(source: $source, endpoint: $endpoint, config: $config);
        $response = $callLog->getResponse();

        if ($response === null && $callLog->getStatusCode() === 429) {
            throw new TooManyRequestsHttpException(
                message: "Rate Limit on Source exceeded.",
                code: 429,
                headers: $this->getRateLimitHeaders($source)
            );
        }

        $result = json_decode($response['body'], true);
        if (empty($result) === true) {
            return [];
        }

        $objects = $this->extractObjects($result, $sourceConfig);

        if ($isTest === true) {
            return isset($objects[0]) === true ? [$objects[0]] : [];
        }

        if ($usesPagination === false) {
            return $objects;
        }

        $currentPage++;
        $nextEndpoint    = $endpoint;
        $newNextEndpoint = null;

        if (array_key_exists('next', $result) === true && $usesNextEndpoint === null) {
            $usesNextEndpoint = true;
        }

        if ($usesNextEndpoint !== false) {
            $newNextEndpoint = $this->getNextEndpoint($result, $source->getLocation());
        } else {
            if ($newNextEndpoint === null && $usesNextEndpoint !== true) {
                $usesNextEndpoint = false;
                $config           = $this->updatePaginationConfig($config, $sourceConfig, $currentPage);
            }
        }

        if (($usesNextEndpoint === true && ($newNextEndpoint === null || $newNextEndpoint === $endpoint))
            || ($usesNextEndpoint === false && ($objects === null || empty($objects) === true))
        ) {
            return $objects;
        }

        return array_merge(
            $objects,
            $this->fetchAllPages(
                source: $source,
                endpoint: ($newNextEndpoint ?? $nextEndpoint),
                config: $config,
                sourceConfig: $sourceConfig,
                currentPage: $currentPage,
                isTest: $isTest,
                usesPagination: $usesPagination,
                usesNextEndpoint: $usesNextEndpoint
            )
        );

    }//end fetchAllPages()


    /**
     * Updates pagination configuration for the next page.
     *
     * @param array $config       Current configuration
     * @param array $sourceConfig Source configuration
     * @param int   $currentPage  Current page number
     *
     * @return array Updated configuration
     */
    private function updatePaginationConfig(array $config, array $sourceConfig, int $currentPage): array
    {
        $config['pagination'] = [
            'paginationQuery' => ($sourceConfig['paginationQuery'] ?? 'page'),
            'page'            => $currentPage,
        ];

        return $config;

    }//end updatePaginationConfig()


    /**
     * Gets the next endpoint URL from the response.
     *
     * @param array  $body    Response body
     * @param string $baseUrl Base URL of the source
     *
     * @return string|null Next endpoint URL or null if none
     */
    private function getNextEndpoint(array $body, string $baseUrl): ?string
    {
        $nextLink = $body['next'] ?? null;
        if ($nextLink === null) {
            return null;
        }

        if (str_starts_with($nextLink, $baseUrl) === true) {
            return substr($nextLink, strlen($baseUrl));
        }

        return $nextLink;

    }//end getNextEndpoint()


}//end class
