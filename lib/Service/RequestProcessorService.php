<?php
/**
 * Service for processing HTTP requests.
 *
 * This service provides utilities for processing HTTP requests, including
 * content parsing, header handling, and request transformation.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service;

use OC\AppFramework\Http\Request;
use OC\AppFramework\Http\RequestId;
use OC\Security\SecureRandom;
use OCP\IConfig;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Service for processing HTTP requests. *
 */
class RequestProcessorService
{
    /**
     * Constructor.
     *
     * @param IConfig         $config  Configuration interface.
     * @param LoggerInterface $logger  Logger for error logging.
     *
     * @return void
     */
    public function __construct(
        private readonly IConfig $config,
        private readonly LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * Get all headers for a HTTP request.
     *
     * Extracts and formats HTTP headers from the server array.
     *
     * @param array $server       The server data from the request.
     * @param bool  $proxyHeaders Whether the proxy headers should be returned.
     *
     * @return array The resulting headers array.
     *
     * @psalm-param array<string, string> $server
     * @psalm-return array<string, string>
     */
    public function getHeaders(array $server, bool $proxyHeaders = false): array
    {
        $headers = array_filter(
            array: $server,
            callback: function (string $key) use ($proxyHeaders) {
                if (str_starts_with($key, 'HTTP_') === false) {
                    return false;
                } else if ($proxyHeaders === false
                    && (str_starts_with(haystack: $key, needle: 'HTTP_X_FORWARDED') === true
                        || $key === 'HTTP_X_REAL_IP' || $key === 'HTTP_X_ORIGINAL_URI'
                    )
                ) {
                    return false;
                }

                return true;
            },
            mode: ARRAY_FILTER_USE_KEY
        );

        $keys = array_keys($headers);

        return array_combine(
            array_map(
                callback: function ($key) {
                    return strtolower(string: substr(string: $key, offset: 5));
                },
                array: $keys
            ),
            $headers
        );
    }//end getHeaders()

    /**
     * Gets the raw content for a http request from the input stream.
     *
     * @return string The raw content body for a http request.
     */
    public function getRawContent(): string
    {
        return file_get_contents(filename: 'php://input');
    }//end getRawContent()

    /**
     * Parse raw content into structured data based on content type.
     *
     * @param string      $content     The raw content to parse.
     * @param string|null $contentType Optional content type hint.
     *
     * @return mixed Parsed data (array for JSON/XML) or original string.
     *
     * @psalm-return array<string, mixed>|string
     */
    public function parseContent(string $content, ?string $contentType = null): mixed
    {
        // Try JSON decode first
        $json = json_decode($content, true);
        if ($json !== null) {
            return $json;
        }

        // Try XML decode if content type suggests XML or content looks like XML
        if ($contentType === 'application/xml' 
            || $contentType === 'text/xml'
            || ($contentType === null && $this->looksLikeXml($content) === true)
        ) {
            libxml_use_internal_errors(true);
            $xml = simplexml_load_string($content);
            libxml_clear_errors();

            if ($xml !== false) {
                return json_decode(json_encode($xml), true);
            }
        }

        // Return original content as fallback
        return $content;
    }//end parseContent()

    /**
     * Check if content appears to be XML.
     *
     * @param string $content Content to check.
     *
     * @return bool True if content is valid XML.
     */
    private function looksLikeXml(string $content): bool
    {
        // Suppress XML errors
        libxml_use_internal_errors(true);

        // Attempt to parse the content as XML
        $result = simplexml_load_string($content) !== false;

        // Clear any XML errors
        libxml_clear_errors();

        return $result;
    }//end looksLikeXml()

    /**
     * Updates request object with processed rule data.
     *
     * @param IRequest $request     The request object to be updated.
     * @param array    $ruleData    The processed rule data to update the request with.
     * @param array    $incomingData The original incoming data.
     *
     * @return IRequest The updated request object.
     *
     * @psalm-param array<string, mixed> $ruleData
     * @psalm-param array<string, mixed> $incomingData
     */
    public function updateRequestWithRuleData(IRequest $request, array $ruleData, array $incomingData): IRequest
    {
        $queryParameters = $ruleData['body']['_parameters'] ?? $incomingData['params'];
        $method = $ruleData['body']['_method'] ?? $incomingData['method'];
        $headers = $ruleData['body']['_headers'] ?? $incomingData['headers'];

        // create items array of request
        $items = [
            'get'          => [],
            'post'         => $_POST,
            'files'        => $_FILES,
            'server'       => $_SERVER,
            'env'          => $_ENV,
            'cookies'      => $_COOKIE,
            'urlParams'    => $queryParameters,
            'params'       => $queryParameters,
            'method'       => $method,
            'requesttoken' => false,
        ];

        $items['server']['headers'] = $headers;

        // build the new request
        $request = new Request(
            vars: $items,
            requestId: new RequestId($request->getId(), new SecureRandom()),
            config: $this->config
        );

        return $request;
    }//end updateRequestWithRuleData()
} 