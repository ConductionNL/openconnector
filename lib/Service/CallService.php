<?php

/**
 * Call Service.
 *
 * This class provides functionality to handle API calls to a specified source within the NextCloud environment.
 * It manages the execution of HTTP requests using the Guzzle HTTP client, while also rendering templates
 * and managing call logs. It utilizes Twig for templating and Guzzle for making HTTP requests, and logs all calls.
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

use Adbar\Dot;
use Exception;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Service\AuthenticationService;
use OCA\OpenConnector\Service\MappingService;
use OCA\OpenConnector\Db\Source;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use OCA\OpenConnector\Db\CallLog;
use OCA\OpenConnector\Db\CallLogMapper;
use OCA\OpenConnector\Twig\AuthenticationExtension;
use OCA\OpenConnector\Twig\AuthenticationRuntimeLoader;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;

/**
 * Call Service.
 */
class CallService
{

    /**
     * HTTP Client for making requests.
     *
     * @var Client Guzzle HTTP client
     */
    private Client $client;

    /**
     * Twig environment for template rendering.
     *
     * @var Environment Twig environment
     */
    private Environment $twig;

    /**
     * The source being used for the current call.
     *
     * @var Source|null
     */
    private ?Source $source = null;


    /**
     * The constructor sets all needed variables.
     *
     * Initializes the service with required dependencies and sets up the HTTP client and Twig environment.
     *
     * @param CallLogMapper         $callLogMapper         Mapper for call logs
     * @param SourceMapper          $sourceMapper          Mapper for sources
     * @param ArrayLoader           $loader                Twig array loader
     * @param AuthenticationService $authenticationService Service for authentication
     *
     * @return void
     */
    public function __construct(
        private readonly CallLogMapper $callLogMapper,
        private readonly SourceMapper $sourceMapper,
        ArrayLoader $loader,
        AuthenticationService $authenticationService
    ) {
        $this->client = new Client([]);
        $this->twig   = new Environment($loader);
        $this->twig->addExtension(new AuthenticationExtension());
        $this->twig->addRuntimeLoader(new AuthenticationRuntimeLoader($authenticationService));

    }//end __construct()


    /**
     * Renders a value using Twig templating if the value contains template syntax.
     *
     * If the value is an array, recursively renders each element.
     *
     * @param array|string $value  The value to render, which can be a string or an array
     * @param Source       $source The source object used as context for rendering templates
     *
     * @return array|string The rendered value, either as a processed string or an array
     *
     * @throws LoaderError If there is an error loading a Twig template
     * @throws SyntaxError If there is a syntax error in a Twig template
     *
     * @psalm-param  array<string, mixed>|string $value
     * @psalm-return array<string, mixed>|string
     */
    private function renderValue($value, Source $source)
    {
        // Check if value is a string containing template syntax.
        if (is_array($value) === false
            && str_contains(haystack: $value, needle: "{{") === true
            && str_contains(haystack: $value, needle: "}}") === true
        ) {
            // Render the string template using Twig.
            $template = $this->twig->createTemplate(template: $value, name: "sourceConfig");
            return $template->render(context: ['source' => $source]);
        } else if (is_array($value) === true) {
            // If value is an array, recursively render each element.
            $value = array_map(
                function ($value) use ($source) {
                    return $this->renderValue($value, $source);
                },
                $value
            );
        }

        return $value;

    }//end renderValue()


    /**
     * Renders configuration values using Twig templating.
     *
     * Applies the provided source as context and recursively processes all values in the configuration array.
     *
     * @param array  $configuration The configuration array to render
     * @param Source $source        The source object used as context for rendering templates
     *
     * @return array The rendered configuration array
     *
     * @throws LoaderError If there is an error loading a Twig template
     * @throws SyntaxError If there is a syntax error in a Twig template
     *
     * @psalm-param  array<string, mixed> $configuration
     * @psalm-return array<string, mixed>
     */
    private function renderConfiguration(array $configuration, Source $source): array
    {
        // Process each value in the configuration array.
        return array_map(
            function ($value) use ($source) {
                return $this->renderValue($value, $source);
            },
            $configuration
        );

    }//end renderConfiguration()


    /**
     * Decides which HTTP method to use based on configuration.
     *
     * @param string $default       The default method, used if no override is set
     * @param array  $configuration The configuration to find overrides in
     * @param bool   $read          For GET as default: decides if we are in a list or read (singular) endpoint
     *
     * @return string The HTTP method to use
     *
     * @psalm-param array<string, mixed> $configuration
     */
    private function decideMethod(string $default, array $configuration, bool $read=false): string
    {
        // Determine the appropriate method based on the request type and configuration.
        switch ($default) {
            case 'POST':
                if (isset($configuration['createMethod']) === true) {
                     return $configuration['createMethod'];
                }
                return $default;
            case 'PUT':
            case 'PATCH':
                if (isset($configuration['updateMethod']) === true) {
                    return $configuration['updateMethod'];
                }
                return $default;
            case 'DELETE':
                if (isset($configuration['destroyMethod']) === true) {
                    return $configuration['destroyMethod'];
                }
                return $default;
            case 'GET':
            default:
                if (isset($configuration['listMethod']) === true && $read === false) {
                    return $configuration['listMethod'];
                }

                if (isset($configuration['readMethod']) === true && $read === true) {
                    return $configuration['readMethod'];
                }
                return $default;
        }//end switch

    }//end decideMethod()


    /**
     * Calls a source according to given configuration.
     *
     * Makes an HTTP request to the specified source with the provided configuration.
     *
     * @param Source $source             The source to call
     * @param string $endpoint           The endpoint on the source to call
     * @param string $method             The method on which to call the source
     * @param array  $config             The additional configuration to call the source
     * @param bool   $asynchronous       Whether to call the source asynchronously
     * @param bool   $createCertificates Whether to create certificates for this source
     * @param bool   $overruleAuth       Whether to override authentication settings
     * @param bool   $read               Whether this is a read operation for a single resource
     *
     * @return CallLog The call log containing request and response information
     *
     * @throws GuzzleException If there is an error with the HTTP request
     * @throws LoaderError If there is an error loading a Twig template
     * @throws SyntaxError If there is a syntax error in a Twig template
     * @throws \OCP\DB\Exception If there is a database error
     *
     * @psalm-param array<string, mixed> $config
     */
    public function call(
        Source $source,
        string $endpoint='',
        string $method='GET',
        array $config=[],
        bool $asynchronous=false,
        bool $createCertificates=true,
        bool $overruleAuth=false,
        bool $read=false
    ): CallLog {
        $this->source = $source;

        // Determine the HTTP method to use.
        $method = $this->decideMethod(default: $method, configuration: $config, read: $read);

        // Unset method override keys from config.
        unset(
            $config['createMethod'],
            $config['updateMethod'],
            $config['destroyMethod'],
            $config['listMethod'],
            $config['readMethod']
        );

        // Check if the source is enabled.
        if ($this->source->getIsEnabled() === null || $this->source->getIsEnabled() === false) {
            // Create and save the CallLog for disabled source.
            $callLog = new CallLog();
            $callLog->setUuid(Uuid::v4());
            $callLog->setSourceId($this->source->getId());
            $callLog->setStatusCode(409);
            $callLog->setStatusMessage("This source is not enabled");
            $callLog->setCreated(new \DateTime());
            $callLog->setExpires(new \DateTime('now + '.$source->getErrorRetention().' seconds'));

            $this->callLogMapper->insert($callLog);

            return $callLog;
        }

        // Check if the source has a location.
        if (empty($this->source->getLocation()) === true) {
            // Create and save the CallLog for missing location.
            $callLog = new CallLog();
            $callLog->setUuid(Uuid::v4());
            $callLog->setSourceId($this->source->getId());
            $callLog->setStatusCode(409);
            $callLog->setStatusMessage("This source has no location");
            $callLog->setCreated(new \DateTime());
            $callLog->setExpires(new \DateTime('now + '.$source->getErrorRetention().' seconds'));

            $this->callLogMapper->insert($callLog);

            return $callLog;
        }

        // Check if Source has a RateLimit and if we need to reset RateLimit-Reset and RateLimit-Remaining.
        if ($this->source->getRateLimitReset() !== null
            && $this->source->getRateLimitRemaining() !== null
            && $this->source->getRateLimitReset() <= time()
        ) {
            $this->source->setRateLimitReset(null);
            $this->source->setRateLimitRemaining(null);

            $this->sourceMapper->update($source);
        }

        // Check if RateLimit-Remaining is set on this source and if limit has been reached.
        if ($this->source->getRateLimitRemaining() !== null && $this->source->getRateLimitRemaining() <= 0) {
            // Create and save the CallLog for rate limit exceeded.
            $callLog = new CallLog();
            $callLog->setUuid(Uuid::v4());
            $callLog->setSourceId($this->source->getId());
            $callLog->setStatusCode(429);
            $callLog->setStatusMessage("The rate limit for this source has been exceeded. Try again later.");
            $callLog->setCreated(new \DateTime());
            $callLog->setExpires(new \DateTime('now + '.$source->getErrorRetention().' seconds'));

            $this->callLogMapper->insert($callLog);

            return $callLog;
        }

        // Check if the source has a configuration and merge it with the given config.
        if (empty($this->source->getConfiguration()) === false) {
            $config = array_merge_recursive($config, $this->applyConfigDot($this->source->getConfiguration()));
        }

        // Check if the config has a Content-Type header and overwrite it if it does.
        if (isset($config['headers']['Content-Type']) === true) {
            $overwriteContentType = $config['headers']['Content-Type'];
        }

        // Decapitalized fall back for content-type.
        if (isset($config['headers']['content-type']) === true) {
            $overwriteContentType = $config['headers']['content-type'];
        }

        // Make sure we do not have an array of accept headers but just one value.
        if (isset($config['headers']['accept']) === true && is_array($config['headers']['accept']) === true) {
            $config['headers']['accept'] = $config['headers']['accept'][0];
        }

        // Check if the config has a headers array and create it if it doesn't.
        if (isset($config['headers']) === false) {
            $config['headers'] = [];
        }

        // Handle pagination configuration.
        if (isset($config['pagination']) === true) {
            $config['query'][$config['pagination']['paginationQuery']] = $config['pagination']['page'];
            unset($config['pagination']);
        }

        // Render configuration templates.
        $config = $this->renderConfiguration(configuration: $config, source: $source);

        // Set the URL to call and add an endpoint if needed.
        $url = $this->source->getLocation().$endpoint;

        // Filter out authentication variables/secrets.
        $config = array_filter(
            $config,
            function ($key) {
                return str_contains(strtolower($key), 'authentication') === false;
            },
            ARRAY_FILTER_USE_KEY
        );

        // Determine if body should be logged.
        $logBody = isset($config['logBody']) === true && (bool) $config['logBody'];
        unset($config['logBody']);

        // Suppress guzzle exceptions to handle them manually.
        $config['http_errors'] = false;

        // Update last call timestamp.
        $this->source->setLastCall(new \DateTime());

        // Make the call.
        $timeStart = microtime(true);
        try {
            if ($asynchronous === false) {
                $response = $this->client->request($method, $url, $config);
            } else {
                // Return async promise for asynchronous calls.
                return $this->client->requestAsync($method, $url, $config);
            }
        } catch (GuzzleException $e) {
            $response = $e->getResponse();
        }

        $timeEnd = microtime(true);

        // Get response body.
        $body = $response->getBody()->getContents();

        // Create data array with request and response details.
        $data = [
            'request'  => [
                'url'    => $url,
                'method' => $method,
                ...$config
            ],
            'response' => [
                'statusCode'    => $response->getStatusCode(),
                'statusMessage' => $response->getReasonPhrase(),
                'responseTime'  => (($timeEnd - $timeStart) * 1000),
                'size'          => $response->getBody()->getSize(),
                'remoteIp'      => $this->getRemoteIp($response),
                'headers'       => $response->getHeaders(),
                'body'          => $this->getEncodedBody($body),
                'encoding'      => $this->getBodyEncoding($body),
            ],
        ];

        // Update Rate Limit info for the source.
        $data['response']['headers'] = $this->sourceRateLimit($source, $data['response']['headers']);

        // Create and save the CallLog.
        $callLog = new CallLog();
        $callLog->setUuid(Uuid::v4());
        $callLog->setSourceId($this->source->getId());
        $callLog->setStatusCode($data['response']['statusCode']);
        $callLog->setStatusMessage($data['response']['statusMessage']);
        $callLog->setRequest($data['request']);
        $callLog->setCreated(new \DateTime());

        // Determine expiration based on response status code.
        $retentionSeconds = $source->getLogRetention();
        if ($data['response']['statusCode'] >= 400) {
            $retentionSeconds = $source->getErrorRetention();
        }

        $callLog->setExpires(new \DateTime('now + '.$retentionSeconds.' seconds'));

        // Only persist response body for errors or if logging is explicitly enabled.
        if (($callLog->getStatusCode() >= 400 && $callLog->getStatusCode() < 600) || $logBody === true) {
            $callLog->setResponse($data['response']);
        } else {
            $response = $data['response'];
            unset($response['body']);
            $callLog->setResponse($response);
        }

        $this->callLogMapper->insert($callLog);

        // Set complete response after persist for further processing.
        $callLog->setResponse($data['response']);

        return $callLog;

    }//end call()


    /**
     * Update the source with rate limit info.
     *
     * Checks response headers for rate limit information and updates the source accordingly.
     * If no headers are found, uses configuration from the source if available.
     *
     * @param Source $source  The source to update
     * @param array  $headers The response headers to check for Rate Limit headers
     *
     * @return array The updated response headers
     *
     * @throws \OCP\DB\Exception If there is a database error
     *
     * @psalm-param  array<string, mixed> $headers
     * @psalm-return array<string, mixed>
     */
    private function sourceRateLimit(Source $source, array $headers): array
    {
        // Check if RateLimit-Reset is present in response headers.
        if (isset($headers['X-RateLimit-Reset']) === true) {
            $source->setRateLimitReset($headers['X-RateLimit-Reset']);
        }

        // If RateLimit-Reset not in headers but Window is set.
        if (isset($headers['X-RateLimit-Reset']) === false
            && $source->getRateLimitReset() === null
            && $source->getRateLimitWindow() !== null
        ) {
            // Set new RateLimit-Reset time on the source.
            $rateLimitReset = (time() + $source->getRateLimitWindow());
            $source->setRateLimitReset($rateLimitReset);
        }

        // Check if RateLimit-Limit is present in response headers.
        if (isset($headers['X-RateLimit-Limit']) === true) {
            $source->setRateLimitLimit($headers['X-RateLimit-Limit']);
        }

        // Check if RateLimit-Remaining is present in response headers.
        if (isset($headers['X-RateLimit-Remaining']) === true) {
            $source->setRateLimitRemaining($headers['X-RateLimit-Remaining']);
        }

        // If RateLimit-Remaining not in headers but Limit is set.
        if (isset($headers['X-RateLimit-Remaining']) === false && $source->getRateLimitLimit() !== null) {
            $rateLimitRemaining = $source->getRateLimitRemaining();
            if ($rateLimitRemaining === null) {
                // Re-set the RateLimit-Remaining on the source.
                $rateLimitRemaining = $source->getRateLimitLimit();
            }

            $source->setRateLimitRemaining($rateLimitRemaining - 1);
        }

        // Update the source with new rate limit values.
        $this->sourceMapper->update($source);

        // Add rate limit info to headers if configured.
        if ($source->getRateLimitLimit() !== null || $source->getRateLimitWindow() !== null) {
            $headers = array_merge(
                $headers,
                [
                    'X-RateLimit-Limit'     => [(string) $source->getRateLimitLimit()],
                    'X-RateLimit-Remaining' => [(string) $source->getRateLimitRemaining()],
                    'X-RateLimit-Reset'     => [(string) $source->getRateLimitReset()],
                    'X-RateLimit-Used'      => ["1"],
                    'X-RateLimit-Window'    => [(string) $source->getRateLimitWindow()],
                ]
            );
            ksort($headers);
        }

        return $headers;

    }//end sourceRateLimit()


    /**
     * Applies dot notation to config keys.
     *
     * Uses Adbar Dot to place the values of keys with a dot in it in the $config array
     * to the correct position in the then updated multidimensional $config array.
     *
     * @param array $config The config array
     *
     * @return array The updated config array
     *
     * @psalm-param  array<string, mixed> $config
     * @psalm-return array<string, mixed>
     */
    public function applyConfigDot(array $config): array
    {
        $dotConfig = new Dot($config);
        $unsetKeys = [];

        // Check if there are keys containing a dot we want to map.
        foreach ($config as $key => $value) {
            if (str_contains($key, '.') === true) {
                $dotConfig->set($key, $value);
                $unsetKeys[] = $key;
            }
        }

        // Remove the old keys containing a dot that we mapped.
        $config = $dotConfig->all();
        foreach ($unsetKeys as $key) {
            unset($config[$key]);
        }

        return $config;

    }//end applyConfigDot()


    /**
     * Fetch an object from a specific endpoint.
     *
     * @param Synchronization $synchronization The synchronization containing the source
     * @param string          $endpoint        The endpoint to request to fetch the desired object
     *
     * @return array The resulting object
     *
     * @throws GuzzleException If there is an HTTP request error
     * @throws Exception If the source is not found
     *
     * @psalm-return array<string, mixed>
     */
    public function fetchObjectFromSource(
        Synchronization $synchronization,
        string $endpoint
    ): array {
        $source = $this->getSource($synchronization->getSourceId());

        // Get source configuration.
        $sourceConfig = $this->applyConfigDot($synchronization->getSourceConfig());

        $config = [];
        if (isset($sourceConfig['headers']) === true && $sourceConfig['headers'] !== '') {
            $config['headers'] = $sourceConfig['headers'];
        }

        if (isset($sourceConfig['query']) === true && $sourceConfig['query'] !== '') {
            $config['query'] = $sourceConfig['query'];
        }

        // Clean endpoint if it contains source location.
        if (str_starts_with($endpoint, $source->getLocation()) === true) {
            $endpoint = str_replace(search: $source->getLocation(), replace: '', subject: $endpoint);
        }

        // Make API call.
        $response = $this->call(
            source: $source,
            endpoint: $endpoint,
            config: $config
        )->getResponse();

        // Return response body as array.
        if (isset($response['body']) === true) {
            $responseBody = json_decode($response['body'], true);
            if ($responseBody === null) {
                return [];
            }

            return $responseBody;
        }

        return [];

    }//end fetchObjectFromSource()


    /**
     * Get a source by ID.
     *
     * @param string $sourceId The ID of the source
     *
     * @return Source The source object
     *
     * @throws Exception If source not found
     */
    private function getSource(string $sourceId): Source
    {
        try {
            return $this->sourceMapper->find($sourceId);
        } catch (Exception $e) {
            throw new Exception("Source with ID $sourceId not found");
        }

    }//end getSource()


    /**
     * Get remote IP from response headers.
     *
     * @param Response $response The HTTP response
     *
     * @return string|null The remote IP or null if not found
     */
    private function getRemoteIp(Response $response): ?string
    {
        $realIp = $response->getHeaderLine('X-Real-IP');
        if (empty($realIp) === false) {
            return $realIp;
        }

        $forwardedFor = $response->getHeaderLine('X-Forwarded-For');
        if (empty($forwardedFor) === false) {
            return $forwardedFor;
        }

        return null;

    }//end getRemoteIp()


    /**
     * Get encoded body content.
     *
     * @param string $body The response body
     *
     * @return string The encoded body
     */
    private function getEncodedBody(string $body): string
    {
        if (mb_check_encoding($body, 'UTF-8') === true) {
            return $body;
        }

        return base64_encode($body);

    }//end getEncodedBody()


    /**
     * Get body encoding type.
     *
     * @param string $body The response body
     *
     * @return string The encoding type
     */
    private function getBodyEncoding(string $body): string
    {
        if (mb_check_encoding($body, 'UTF-8') === true) {
            return 'UTF-8';
        }

        return 'base64';

    }//end getBodyEncoding()


}//end class
