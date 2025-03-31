<?php

/**
 * This file is part of the OpenConnector app.
 *
 * @package   OpenConnector
 * @category  Service
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 * @version   1.0.0
 */
namespace OCA\OpenConnector\Service\SourceHandler;

use OCA\OpenConnector\Db\Source;
use SimpleXMLElement;

/**
 * XML Source Handler.
 *
 * Handler for processing XML sources and converting data between XML and array formats.
 *
 * @package   OpenConnector
 * @category  Service
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
class XmlHandler extends AbstractSourceHandler
{


    /**
     * Checks if this handler can handle the given source type.
     *
     * Determines if this handler is appropriate for the specified source type.
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
        return $sourceType === 'xml';

    }//end canHandle()


    /**
     * Gets all objects from an XML source.
     *
     * Fetches and processes XML data from the source, converting it to array format.
     *
     * @param Source $source      The source to fetch from
     * @param array  $config      Configuration for the source
     * @param bool   $isTest      Whether this is a test run, defaults to false
     * @param int    $currentPage Current page for pagination, defaults to 1
     * @param array  $headers     Optional headers for the request, defaults to empty array
     * @param array  $query       Optional query parameters, defaults to empty array
     *
     * @return array Array of objects fetched from the source
     *
     * @throws \Exception If there is an error fetching the objects
     *
     * @psalm-param  array<string, mixed> $config
     * @psalm-param  array<string, string> $headers
     * @psalm-param  array<string, mixed> $query
     * @psalm-return array<int, array<string, mixed>>
     */
    public function getAllObjects(
        Source $source,
        array $config,
        bool $isTest=false,
        int $currentPage=1,
        array $headers=[],
        array $query=[]
    ): array {
        // Check rate limit before making the call
        $this->checkRateLimit($source);

        // Prepare endpoint and request configuration
        $endpoint      = ($config['endpoint'] ?? '');
        $requestConfig = [
            'headers' => $headers,
            'query'   => $query,
        ];

        // Make the API call to the source
        $response = $this->callService->call(
            source: $source,
            endpoint: $endpoint,
            config: $requestConfig
        )->getResponse();

        // Return empty array if no response
        if ($response === null) {
            return [];
        }

        // Parse the XML response
        $xml = $this->parseXmlResponse($response['body']);
        if ($xml === false) {
            return [];
        }

        // Convert XML to array and extract objects
        $result  = $this->xmlToArray($xml);
        $objects = $this->extractObjects($result, $config);

        // If this is a test run, return only the first object
        if ($isTest === true && empty($objects) === false) {
            return [$objects[0]];
        }

        return $objects;

    }//end getAllObjects()


    /**
     * Fetches a single object from the source.
     *
     * Retrieves and processes a single XML object from the specified endpoint.
     *
     * @param Source $source   The source to fetch from
     * @param string $endpoint The endpoint to fetch from
     * @param array  $config   Configuration for the source
     * @param array  $headers  Optional headers for the request, defaults to empty array
     * @param array  $query    Optional query parameters, defaults to empty array
     *
     * @return array The fetched object as an array
     *
     * @throws \Exception If there is an error fetching the object
     *
     * @psalm-param  array<string, mixed> $config
     * @psalm-param  array<string, string> $headers
     * @psalm-param  array<string, mixed> $query
     * @psalm-return array<string, mixed>
     */
    public function getObject(
        Source $source,
        string $endpoint,
        array $config,
        array $headers=[],
        array $query=[]
    ): array {
        // Check rate limit before making the call
        $this->checkRateLimit($source);

        // Remove base URL from endpoint if present
        if (str_starts_with($endpoint, $source->getLocation()) === true) {
            $endpoint = str_replace(search: $source->getLocation(), replace: '', subject: $endpoint);
        }

        // Prepare request configuration
        $requestConfig = [
            'headers' => $headers,
            'query'   => $query,
        ];

        // Make the API call to the source
        $response = $this->callService->call(
            source: $source,
            endpoint: $endpoint,
            config: $requestConfig,
            read: true
        )->getResponse();

        // Parse the XML response
        $xml = $this->parseXmlResponse($response['body']);
        if ($xml === false) {
            return [];
        }

        // Convert XML to array and return
        return $this->xmlToArray($xml);

    }//end getObject()


    /**
     * Parses an XML string into a SimpleXMLElement.
     *
     * Converts a raw XML string to a SimpleXMLElement object for processing.
     *
     * @param string $xmlString The XML string to parse
     *
     * @return \SimpleXMLElement|false The parsed XML or false on failure
     */
    private function parseXmlResponse(string $xmlString): \SimpleXMLElement | false
    {
        // Suppress XML parsing errors by using internal error handling
        libxml_use_internal_errors(true);

        // Parse the XML string, ignoring CDATA sections
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);

        // Restore default error handling
        libxml_use_internal_errors(false);

        return $xml;

    }//end parseXmlResponse()


    /**
     * Converts a SimpleXMLElement to an array while preserving namespaced attributes.
     *
     * Recursively transforms XML elements into a structured array representation.
     *
     * @param \SimpleXMLElement $xml The XML element to convert
     *
     * @return array The array representation of the XML element
     *
     * @psalm-return array<string, mixed>
     */
    private function xmlToArray(\SimpleXMLElement $xml): array
    {
        $result = [];

        // Handle attributes.
        $attributes = $xml->attributes();
        if (count($attributes) > 0) {
            $result['@attributes'] = [];
            foreach ($attributes as $attrName => $attrValue) {
                $result['@attributes'][(string) $attrName] = (string) $attrValue;
            }
        }

        // Handle namespaced attributes.
        $namespaces = $xml->getNamespaces(true);
        foreach ($namespaces as $prefix => $namespace) {
            $nsAttributes = $xml->attributes($namespace);
            if (count($nsAttributes) > 0) {
                if (isset($result['@attributes']) === false) {
                    $result['@attributes'] = [];
                }

                foreach ($nsAttributes as $attrName => $attrValue) {
                    $nsAttrName = $prefix ? "$prefix:$attrName" : $attrName;
                    $result['@attributes'][$nsAttrName] = (string) $attrValue;
                }
            }
        }

        // Handle child elements.
        foreach ($xml->children() as $childName => $child) {
            $childArray = $this->xmlToArray($child);

            if (isset($result[$childName]) === true) {
                if (is_array($result[$childName]) === false || isset($result[$childName][0]) === false) {
                    $result[$childName] = [$result[$childName]];
                }

                $result[$childName][] = $childArray;
            } else {
                $result[$childName] = $childArray;
            }
        }

        // Handle text content.
        $text = trim((string) $xml);
        if (count($result) === 0 && $text !== '') {
            return ['#text' => $text];
        } else if ($text !== '') {
            $result['#text'] = $text;
        }

        return $result;

    }//end xmlToArray()


}//end class
