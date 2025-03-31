<?php
/**
 * Service for handling endpoint operations.
 *
 * This service provides the main functionality for handling requests to
 * endpoints, delegating processing to appropriate handlers and managing rules.
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

use Exception;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Service\Handler\RequestHandlerInterface;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

/**
 * Service for handling endpoint operations.
 */
class EndpointService
{
	/**
     * The registered request handlers.
     *
     * @var RequestHandlerInterface[]
     */
    private array $requestHandlers = [];

    /**
     * Constructor.
     *
     * @param RuleProcessorService    $ruleProcessor    Service for processing endpoint rules.
     * @param RequestProcessorService $requestProcessor Service for processing HTTP requests.
     * @param IURLGenerator           $urlGenerator     URL generator for creating endpoint URLs.
     * @param LoggerInterface         $logger           Logger for error logging.
	 *
	 * @return void
	 */
	public function __construct(
        private readonly RuleProcessorService $ruleProcessor,
        private readonly RequestProcessorService $requestProcessor,
        private readonly IURLGenerator $urlGenerator,
        private readonly LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * Register a request handler.
     *
     * @param RequestHandlerInterface $handler The request handler to register.
     *
     * @return void
     */
    public function registerRequestHandler(RequestHandlerInterface $handler): void
    {
        $this->requestHandlers[] = $handler;
    }//end registerRequestHandler()

    /**
     * Handle a request to an endpoint.
     *
     * This is the main entry point for processing endpoint requests, which delegates
     * to appropriate handlers and manages rule processing.
     *
     * @param Endpoint $endpoint The endpoint to handle the request for.
     * @param IRequest $request  The incoming request.
     * @param string   $path     The path portion of the request URL.
     *
     * @return JSONResponse The response from the endpoint.
     *
     * @throws Exception When no handler can be found for the endpoint type.
	 */
	public function handleRequest(Endpoint $endpoint, IRequest $request, string $path): JSONResponse
	{
        // Extract request data
        $requestData = [
            'method' => $request->getMethod(),
            'params' => $request->getParams(),
            'headers' => $this->requestProcessor->getHeaders($request->server),
            'body' => $this->requestProcessor->parseContent(
                $this->requestProcessor->getRawContent(),
				$request->getHeader('Content-Type')
            )
        ];

        // Process pre-request rules
        $preRuleResult = $this->ruleProcessor->processRules(
				endpoint: $endpoint,
				request: $request,
            data: $requestData,
				timing: 'before'
			);

        // If pre-rule processing resulted in an error response, return it immediately
        if ($preRuleResult instanceof JSONResponse) {
            return $preRuleResult;
        }

        // Update request with pre-rule processing results if needed
        if ($preRuleResult !== $requestData) {
            $request = $this->requestProcessor->updateRequestWithRuleData(
					request: $request,
                ruleData: $preRuleResult,
                incomingData: $requestData
            );
        }

        // Find appropriate handler for the endpoint type
        $handler = $this->findHandlerForEndpoint($endpoint);
        if ($handler === null) {
            throw new Exception(
                sprintf('No handler found for endpoint type: %s', $endpoint->getTargetType())
            );
        }

        // Handle the request
        $response = $handler->handle($endpoint, $request, $path, $preRuleResult);

        // Process post-request rules if not an error response
        if ($response->getStatus() >= 200 && $response->getStatus() < 300) {
            $responseData = [
                'method' => $request->getMethod(),
                'params' => $request->getParams(),
                'headers' => $requestData['headers'],
                'body' => $response->getData()
            ];

            $postRuleResult = $this->ruleProcessor->processRules(
                endpoint: $endpoint,
                request: $request,
                data: $responseData,
                timing: 'after'
            );

            // If post-rule processing resulted in a new response, return it
            if ($postRuleResult instanceof JSONResponse) {
                return $postRuleResult;
            }

            // Otherwise update the response data if it changed
            if ($postRuleResult['body'] !== $responseData['body']) {
                $response = new JSONResponse(
                    $postRuleResult['body'],
                    $response->getStatus(),
                    $response->getHeaders()
                );
            }
        }

        return $response;
    }//end handleRequest()

    /**
     * Generates a URL for an endpoint.
     *
     * @param string      $id           The ID or UUID to include in the URL.
     * @param array|null  $queryParams  Optional query parameters to include.
     * @param bool        $isPathParam  Whether the ID is a path parameter or a query parameter.
     * @param string|null $pathTemplate Optional template for the path structure.
     *
     * @return string The generated URL.
     *
     * @psalm-param array<string, mixed>|null $queryParams
     */
    public function generateEndpointUrl(
        string $id,
        ?array $queryParams = null,
        bool $isPathParam = true,
        ?string $pathTemplate = null
    ): string {
        $url = $this->urlGenerator->getBaseUrl() . '/apps/openconnector/api/endpoint/';

        if ($pathTemplate !== null) {
            // Replace placeholders in template
            $url .= str_replace('{{id}}', $id, $pathTemplate);
        } else if ($isPathParam === true) {
            // Default: append ID to path
            $url .= $id;
		} else {
            // Add ID as query parameter
            if ($queryParams === null) {
                $queryParams = [];
            }
            $queryParams['id'] = $id;
        }

        // Add query parameters if any
        if ($queryParams !== null && count($queryParams) > 0) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $url;
    }//end generateEndpointUrl()

    /**
     * Find a handler for the given endpoint type.
     *
     * @param Endpoint $endpoint The endpoint to find a handler for.
     *
     * @return RequestHandlerInterface|null The handler that can process the endpoint, or null if none found.
     */
    private function findHandlerForEndpoint(Endpoint $endpoint): ?RequestHandlerInterface
    {
        foreach ($this->requestHandlers as $handler) {
            if ($handler->canHandle($endpoint)) {
                return $handler;
            }
        }

        return null;
    }//end findHandlerForEndpoint()
}
