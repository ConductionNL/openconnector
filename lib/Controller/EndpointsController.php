<?php

namespace OCA\OpenConnector\Controller;

use Exception;
use OCA\OpenConnector\Http\XMLResponse;
use OCA\OpenConnector\Service\AuthorizationService;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\EndpointService;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Db\EndpointLogMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Controller for handling endpoint related operations
 */
class EndpointsController extends Controller
{
	/**
	 * CORS allowed methods
	 *
	 * @var string
	 */
	private string $corsMethods;

	/**
	 * CORS allowed headers
	 *
	 * @var string
	 */
	private string $corsAllowedHeaders;

	/**
	 * CORS max age
	 *
	 * @var int
	 */
	private int $corsMaxAge;

	/**
	 * EndpointLogMapper
	 *
	 * @var EndpointLogMapper
	 */
	private EndpointLogMapper $endpointLogMapper;

	/**
	 * Constructor for the EndpointsController
	 *
	 * @param string $appName The name of the app
	 * @param IRequest $request The request object
	 * @param IAppConfig $config The app configuration object
	 * @param EndpointMapper $endpointMapper The endpoint mapper object
	 * @param EndpointService $endpointService Service for handling endpoint operations
	 * @param EndpointLogMapper $endpointLogMapper The endpoint log mapper object
	 */
	public function __construct(
		$appName,
		IRequest $request,
		private IAppConfig $config,
		private EndpointMapper $endpointMapper,
		private EndpointService $endpointService,
		private AuthorizationService $authorizationService,
		EndpointLogMapper $endpointLogMapper,
		$corsMethods = 'PUT, POST, GET, DELETE, PATCH',
		$corsAllowedHeaders = 'Authorization, Content-Type, Accept',
		$corsMaxAge = 1728000
	)
	{
		parent::__construct($appName, $request);
        $this->corsMethods = $corsMethods;
        $this->corsAllowedHeaders = $corsAllowedHeaders;
        $this->corsMaxAge = $corsMaxAge;
        $this->endpointLogMapper = $endpointLogMapper;
	}

	/**
	 * Returns the template of the main app's page
	 *
	 * This method renders the main page of the application, adding any necessary data to the template.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse The rendered template response
	 */
	public function page(): TemplateResponse
	{
		return new TemplateResponse(
			'openconnector',
			'index',
			[]
		);
	}

	/**
	 * Retrieves a list of all endpoints
	 *
	 * This method returns a JSON response containing an array of all endpoints in the system.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return JSONResponse A JSON response containing the list of endpoints
	 */
	public function index(ObjectService $objectService, SearchService $searchService): JSONResponse
	{
		$filters = $this->request->getParams();
		$fieldsToSearch = ['name', 'description', 'endpoint'];

		$searchParams = $searchService->createMySQLSearchParams(filters: $filters);
		$searchConditions = $searchService->createMySQLSearchConditions(filters: $filters, fieldsToSearch: $fieldsToSearch);
		$filters = $searchService->unsetSpecialQueryParams(filters: $filters);

		return new JSONResponse(['results' => $this->endpointMapper->findAll(limit: null, offset: null, filters: $filters, searchConditions: $searchConditions, searchParams: $searchParams)]);
	}

	/**
	 * Retrieves a single endpoint by its ID
	 *
	 * This method returns a JSON response containing the details of a specific endpoint.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $id The ID of the endpoint to retrieve
	 * @return JSONResponse A JSON response containing the endpoint details
	 */
	public function show(string $id): JSONResponse
	{
		try {
			return new JSONResponse($this->endpointMapper->find(id: (int)$id));
		} catch (DoesNotExistException $exception) {
			return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
		}
	}

	/**
	 * Creates a new endpoint
	 *
	 * This method creates a new endpoint based on POST data.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return JSONResponse A JSON response containing the created endpoint
	 */
	public function create(): JSONResponse
	{
		$data = $this->request->getParams();

		foreach ($data as $key => $value) {
			if (str_starts_with($key, '_') === true) {
				unset($data[$key]);
			}
		}

		if (isset($data['id']) === true) {
			unset($data['id']);
		}

		$endpoint = $this->endpointMapper->createFromArray(object: $data);

		return new JSONResponse($endpoint);
	}

	/**
	 * Updates an existing endpoint
	 *
	 * This method updates an existing endpoint based on its ID.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id The ID of the endpoint to update
	 * @return JSONResponse A JSON response containing the updated endpoint details
	 */
	public function update(int $id): JSONResponse
	{
		$data = $this->request->getParams();

		foreach ($data as $key => $value) {
			if (str_starts_with($key, '_') === true) {
				unset($data[$key]);
			}
		}
		if (isset($data['id']) === true) {
			unset($data['id']);
		}

		$endpoint = $this->endpointMapper->updateFromArray(id: (int)$id, object: $data);

		return new JSONResponse($endpoint);
	}

	/**
	 * Deletes an endpoint
	 *
	 * This method deletes an endpoint based on its ID.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id The ID of the endpoint to delete
	 * @return JSONResponse An empty JSON response
	 * @throws \OCP\DB\Exception
	 */
	public function destroy(int $id): JSONResponse
	{
		$this->endpointMapper->delete($this->endpointMapper->find((int)$id));

		return new JSONResponse([]);
	}

	/**
	 * Handles generic path requests by matching against registered endpoints
	 *
	 * This method checks if the current path matches any registered endpoint patterns
	 * and forwards the request to the appropriate endpoint service if found
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @PublicPage
	 *
	 * @param string $_path
	 * @return JSONResponse|XMLResponse The response from the endpoint service or 404 if no match
	 * @throws Exception
	 */
	public function handlePath(string $_path): Response
	{
		// Find matching endpoints for the given path and method
		$matchingEndpoints = $this->endpointMapper->findByPathRegex(
			path: $_path,
			method: $this->request->getMethod()
		);

		// If no matching endpoints found, return 404
		if (empty($matchingEndpoints) === true) {
			return new JSONResponse(
				data: ['error' => 'No matching endpoint found for path and method: ' . $_path . ' ' . $this->request->getMethod()],
				statusCode: 404
			);
		}

		// If no matching endpoints found, return 404
		if (count($matchingEndpoints) > 1) {
			return new JSONResponse(
				data: ['error' => 'Multiple endpoints found for path and method: ' . $_path . ' ' . $this->request->getMethod()],
				statusCode: 409
			);
		}

		// Get the first matching endpoint since we have already filtered by method
		$endpoint = reset($matchingEndpoints);

		// Forward the request to the endpoint service
		$response = $this->endpointService->handleRequest($endpoint, $this->request, $_path);

		// Check if the Accept header is set to XML
		$acceptHeader = $this->request->getHeader('Accept');
		if (stripos($acceptHeader, 'application/xml') !== false && $response instanceof JSONResponse === true) {
			// Convert JSON response to XML response
			$response = new XMLResponse($response->getData(), $response->getStatus(), $response->getHeaders(), $_path);
		}

        return $this->authorizationService->corsAfterController($this->request, $response);
	}

    /**
     * Implements a preflighted CORS response for OPTIONS requests.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @since 7.0.0
     *
     * @return Response The CORS response
     */
    #[NoCSRFRequired]
    #[PublicPage]
    public function preflightedCors(): Response {
        // Determine the origin
        $origin = isset($this->request->server['HTTP_ORIGIN']) === true ? $this->request->server['HTTP_ORIGIN'] : '*';

        // Create and configure the response
        $response = new Response();
        $response->addHeader('Access-Control-Allow-Origin', $origin);
        $response->addHeader('Access-Control-Allow-Methods', $this->corsMethods);
        $response->addHeader('Access-Control-Max-Age', (string)$this->corsMaxAge);
        $response->addHeader('Access-Control-Allow-Headers', $this->corsAllowedHeaders);
        $response->addHeader('Access-Control-Allow-Credentials', 'false');

        return $response;
    }

    /**
     * Retrieves endpoint logs with filtering and pagination support
     *
     * This method returns endpoint logs based on query parameters,
     * with support for various filtering parameters to narrow down the results.
     *
     * Query Parameters:
     * - endpoint_id: Filter logs by endpoint ID
     * - date_from: Filter logs created after this date
     * - date_to: Filter logs created before this date
     * - method: Filter logs by HTTP method
     * - status_code: Filter logs by status code range (comma-separated min,max)
     * - slow_requests: Filter logs with response time > 5000ms
     * - limit: Number of results per page (default: 20)
     * - offset: Offset for pagination (default: 0)
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the filtered endpoint logs and pagination
     */
    public function logs(SearchService $searchService): JSONResponse
    {
        try {
            // Get filters from request
            $filters = $this->request->getParams();
            $specialFilters = [];

            // Pagination using _page and _limit
            $limit = isset($filters['_limit']) ? (int)$filters['_limit'] : 20;
            $page = isset($filters['_page']) ? (int)$filters['_page'] : 1;
            $offset = ($page - 1) * $limit;
            unset($filters['_limit'], $filters['_page']);

            // Handle special filters
            if (!empty($filters['date_from'])) {
                $specialFilters['date_from'] = $filters['date_from'];
            }
            if (!empty($filters['date_to'])) {
                $specialFilters['date_to'] = $filters['date_to'];
            }
            if (!empty($filters['method'])) {
                $specialFilters['method'] = $filters['method'];
            }
            if (!empty($filters['status_code'])) {
                $statusCodes = explode(',', $filters['status_code']);
                if (count($statusCodes) === 2) {
                    $specialFilters['status_code_range'] = $statusCodes;
                }
            }
            if (!empty($filters['slow_requests'])) {
                $specialFilters['slow_requests'] = 5000; // 5 seconds in milliseconds
            }

            // Build search conditions and parameters
            $searchConditions = [];
            $searchParams = [];

            if (!empty($specialFilters['date_from'])) {
                $searchConditions[] = "created >= ?";
                $searchParams[] = $specialFilters['date_from'];
            }

            if (!empty($specialFilters['date_to'])) {
                $searchConditions[] = "created <= ?";
                $searchParams[] = $specialFilters['date_to'];
            }

            if (!empty($specialFilters['method'])) {
                $searchConditions[] = "method = ?";
                $searchParams[] = $specialFilters['method'];
            }

            if (!empty($specialFilters['status_code_range'])) {
                $searchConditions[] = "status_code >= ? AND status_code <= ?";
                $searchParams = array_merge($searchParams, $specialFilters['status_code_range']);
            }

            if (!empty($specialFilters['slow_requests'])) {
                $searchConditions[] = "JSON_EXTRACT(response, '$.responseTime') > ?";
                $searchParams[] = $specialFilters['slow_requests'];
            }

            // Remove special query params from filters
            $filters = $searchService->unsetSpecialQueryParams(filters: $filters);

            // Get endpoint logs with filters and pagination
            $endpointLogs = $this->endpointLogMapper->findAll(
                limit: $limit,
                offset: $offset,
                filters: $filters,
                searchConditions: $searchConditions,
                searchParams: $searchParams
            );

            // Get total count for pagination
            $total = $this->endpointLogMapper->getTotalCount($filters);
            $pages = $limit > 0 ? ceil($total / $limit) : 1;
            $currentPage = $limit > 0 ? floor($offset / $limit) + 1 : 1;

            // Return flattened paginated response
            return new JSONResponse([
                'results' => $endpointLogs,
                'page' => $currentPage,
                'pages' => $pages,
                'results_count' => count($endpointLogs),
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => 'Failed to retrieve logs: ' . $e->getMessage()], 500);
        }
    }

}
