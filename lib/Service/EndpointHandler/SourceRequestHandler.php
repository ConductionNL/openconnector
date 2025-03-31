<?php
/**
 * This file is part of the OpenConnector app.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service\EndpointHandler;

use GuzzleHttp\Exception\GuzzleException;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Service\CallService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;

/**
 * Handler for source-based endpoint requests.
 *
 * This class handles requests to source-based endpoints, which connect to
 * an external API source through the CallService.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
class SourceRequestHandler implements RequestHandlerInterface
{
    /**
     * Constructor.
     *
     * @param CallService     $callService Service for making external API calls.
     * @param LoggerInterface $logger      Logger for error logging.
     *
     * @return void
     */
    public function __construct(
        private readonly CallService $callService,
        private readonly LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * Determines if this handler can handle the given endpoint.
     *
     * @param Endpoint $endpoint The endpoint to check.
     *
     * @return bool True if this handler can handle the endpoint, false otherwise.
     */
    public function canHandle(Endpoint $endpoint): bool
    {
        return $endpoint->getTargetType() === 'api';
    }//end canHandle()

    /**
     * Handles the request to the given endpoint.
     *
     * @param Endpoint  $endpoint The endpoint configuration.
     * @param IRequest  $request  The incoming request.
     * @param string    $path     The path from the request.
     * @param array     $data     Additional data needed for handling the request.
     *
     * @return JSONResponse Response containing the result of the request.
     *
     * @throws GuzzleException If there is an error with HTTP communication.
     * @throws LoaderError If there is an error loading templates.
     * @throws SyntaxError If there is a syntax error in templates.
     * @throws \OCP\DB\Exception If there is a database error.
     *
     * @psalm-param array<string, mixed> $data
     */
    public function handle(Endpoint $endpoint, IRequest $request, string $path, array $data): JSONResponse
    {
        $headers = $this->getHeaders($request->server);

        // Proxy the request to the source via CallService
        $response = $this->callService->call(
            source: $endpoint->getSource(),
            endpoint: $endpoint->getPath(),
            method: $request->getMethod(),
            config: [
                'query' => $request->getParams(),
                'headers' => $headers,
                'body' => $this->getRawContent(),
            ]
        );

        return new JSONResponse(
            $response->getResponse(),
            $response->getStatusCode()
        );
    }//end handle()

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
    private function getHeaders(array $server, bool $proxyHeaders = false): array
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
    private function getRawContent(): string
    {
        return file_get_contents(filename: 'php://input');
    }//end getRawContent()
} 