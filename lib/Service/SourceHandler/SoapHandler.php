<?php

/**
 * Handler for SOAP sources without requiring PHP SOAP extension.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Description of when this class was added
 */

namespace OCA\OpenConnector\Service\SourceHandler;

use InvalidArgumentException;
use SimpleXMLElement;

/**
 * Handler for SOAP sources without requiring PHP SOAP extension.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V. <info@conduction.nl>
 * @copyright 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Description of when this class was added
 */
class SoapHandler extends AbstractSourceHandler
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
        return $sourceType === 'soap';

    }//end canHandle()


    /**
     * Gets all objects from a SOAP source.
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
     * @throws InvalidArgumentException When SOAP action or operation is not specified
     * @throws \Exception If there is an error fetching the objects
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
        $this->checkRateLimit($source);

        if ((isset($config['soapAction']) === false) || (isset($config['operation']) === false)) {
            throw new InvalidArgumentException('SOAP action and operation must be specified in config');
        }

        $soapRequest = $this->buildSoapRequest(
            operation: $config['operation'],
            parameters: $query,
            namespace: $config['namespace'] ?? null,
            soapVersion: ($config['soapVersion'] ?? '1.1')
        );

        $headers = array_merge(
            $headers,
            [
                'Content-Type' => 'text/xml;charset=UTF-8',
                'SOAPAction'   => $config['soapAction'],
            ]
        );

        $requestConfig = [
            'headers' => $headers,
            'body'    => $soapRequest,
        ];

        $response = $this->callService->call(
            source: $source,
            endpoint: ($config['endpoint'] ?? ''),
            method: 'POST',
            config: $requestConfig
        )->getResponse();

        if ($response === null) {
            return [];
        }

        $result  = $this->parseSoapResponse($response['body']);
        $objects = $this->extractObjects($result, $config);

        if (($isTest === true) && (empty($objects) === false)) {
            return [$objects[0]];
        }

        return $objects;

    }//end getAllObjects()


    /**
     * Gets a single object from a SOAP source.
     *
     * @param Source $source   The source to fetch from
     * @param string $endpoint The endpoint to fetch from
     * @param array  $config   Configuration for the source
     * @param array  $headers  Optional headers for the request
     * @param array  $query    Optional query parameters
     *
     * @return array The fetched object
     *
     * @throws InvalidArgumentException When SOAP action or operation is not specified
     * @throws \Exception If there is an error fetching the object
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
        $this->checkRateLimit($source);

        if ((isset($config['soapAction']) === false) || (isset($config['operation']) === false)) {
            throw new InvalidArgumentException('SOAP action and operation must be specified in config');
        }

        $soapRequest = $this->buildSoapRequest(
            operation: $config['operation'],
            parameters: $query,
            namespace: $config['namespace'] ?? null,
            soapVersion: ($config['soapVersion'] ?? '1.1')
        );

        $headers = array_merge(
            $headers,
            [
                'Content-Type' => 'text/xml;charset=UTF-8',
                'SOAPAction'   => $config['soapAction'],
            ]
        );

        $requestConfig = [
            'headers' => $headers,
            'body'    => $soapRequest,
        ];

        $response = $this->callService->call(
            source: $source,
            endpoint: $endpoint,
            method: 'POST',
            config: $requestConfig,
            read: true
        )->getResponse();

        return $this->parseSoapResponse($response['body']);

    }//end getObject()


    /**
     * Builds a SOAP request XML.
     *
     * @param string      $operation   The SOAP operation to call
     * @param array       $parameters  The parameters for the operation
     * @param string|null $namespace   Optional namespace
     * @param string      $soapVersion SOAP version to use ('1.1' or '1.2')
     *
     * @return string The SOAP request XML
     *
     * @phpstan-param array<string, mixed> $parameters
     */
    private function buildSoapRequest(
        string $operation,
        array $parameters,
        ?string $namespace=null,
        string $soapVersion='1.1'
    ): string {
        $nsPrefix = $namespace ? 'ns1:' : '';
        $ns       = $namespace ? " xmlns:ns1=\"$namespace\"" : '';
        $envelope = $soapVersion === '1.2' ? 'http://www.w3.org/2003/05/soap-envelope' : 'http://schemas.xmlsoap.org/soap/envelope/';

        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<SOAP-ENV:Envelope xmlns:SOAP-ENV=\"$envelope\"$ns>\n";
        $xml .= "  <SOAP-ENV:Body>\n";
        $xml .= "    <{$nsPrefix}{$operation}>\n";

        // Add parameters.
        foreach ($parameters as $key => $value) {
            $xml .= $this->parameterToXml($key, $value, '      ');
        }

        $xml .= "    </{$nsPrefix}{$operation}>\n";
        $xml .= "  </SOAP-ENV:Body>\n";
        $xml .= "</SOAP-ENV:Envelope>";

        return $xml;

    }//end buildSoapRequest()


    /**
     * Converts a parameter to XML format.
     *
     * @param string $key    The parameter name
     * @param mixed  $value  The parameter value
     * @param string $indent Indentation string
     *
     * @return string The XML representation of the parameter
     *
     * @phpstan-param string|int|float|bool|array|null $value
     */
    private function parameterToXml(string $key, mixed $value, string $indent): string
    {
        if (is_array($value) === true) {
            $xml = "$indent<$key>\n";
            foreach ($value as $subKey => $subValue) {
                $xml .= $this->parameterToXml($subKey, $subValue, $indent.'  ');
            }

            $xml .= "$indent</$key>\n";
            return $xml;
        }

        return "$indent<$key>".htmlspecialchars((string) $value)."</$key>\n";

    }//end parameterToXml()


    /**
     * Parses a SOAP response XML.
     *
     * @param string $response The SOAP response XML
     *
     * @return array The parsed response
     *
     * @phpstan-return array<mixed>
     */
    private function parseSoapResponse(string $response): array
    {
        $xml = $this->parseXmlResponse($response);
        if ($xml === false) {
            return [];
        }

        // Remove SOAP envelope and get to the actual response data.
        $body = $xml->children('http://schemas.xmlsoap.org/soap/envelope/')->Body;
        if ($body === false) {
            $body = $xml->children('http://www.w3.org/2003/05/soap-envelope')->Body;
        }

        if ($body === false) {
            return [];
        }

        // Convert the response to array.
        return $this->xmlToArray($body->children()[0]);

    }//end parseSoapResponse()


    /**
     * Parses an XML string into a SimpleXMLElement.
     *
     * @param string $xmlString The XML string to parse
     *
     * @return SimpleXMLElement|false The parsed XML or false on failure
     */
    private function parseXmlResponse(string $xmlString): SimpleXMLElement | false
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString, "SimpleXMLElement", LIBXML_NOCDATA);
        libxml_use_internal_errors(false);

        return $xml;

    }//end parseXmlResponse()


    /**
     * Converts a SimpleXMLElement to an array while preserving namespaced attributes.
     *
     * @param SimpleXMLElement $xml The XML element to convert
     *
     * @return array The array representation
     *
     * @phpstan-return array<string, mixed>
     */
    private function xmlToArray(SimpleXMLElement $xml): array
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
                    $nsAttrName                         = $prefix ? "$prefix:$attrName" : $attrName;
                    $result['@attributes'][$nsAttrName] = (string) $attrValue;
                }
            }
        }

        // Handle child elements.
        foreach ($xml->children() as $childName => $child) {
            $childArray = $this->xmlToArray($child);

            if (isset($result[$childName]) === true) {
                if ((is_array($result[$childName]) === false) || (isset($result[$childName][0]) === false)) {
                    $result[$childName] = [$result[$childName]];
                }

                $result[$childName][] = $childArray;
            } else {
                $result[$childName] = $childArray;
            }
        }

        // Handle text content.
        $text = trim((string) $xml);
        if ((count($result) === 0) && ($text !== '')) {
            return ['#text' => $text];
        } else if ($text !== '') {
            $result['#text'] = $text;
        }

        return $result;

    }//end xmlToArray()


}//end class
