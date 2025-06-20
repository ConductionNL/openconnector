<?php

namespace OCA\OpenConnector\Controller;

use GuzzleHttp\Exception\GuzzleException;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Db\SynchronizationLogMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use Exception;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class SynchronizationsController extends Controller
{
    /**
     * Constructor for the SynchronizationsController
     *
     * @param string $appName The name of the app
     * @param IRequest $request The request object
     * @param IAppConfig $config The app configuration object
     */
    public function __construct(
        $appName,
        IRequest $request,
        private readonly IAppConfig $config,
        private readonly SynchronizationMapper $synchronizationMapper,
        private readonly SynchronizationContractMapper $synchronizationContractMapper,
        private readonly SynchronizationLogMapper $synchronizationLogMapper,
        private readonly SynchronizationService $synchronizationService
    )
    {
        parent::__construct($appName, $request);

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
     * Retrieves a list of all synchronizations
     *
     * This method returns a JSON response containing an array of all synchronizations in the system.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the list of synchronizations
     */
    public function index(ObjectService $objectService, SearchService $searchService): JSONResponse
    {
        $filters = $this->request->getParams();
        $fieldsToSearch = ['name', 'description'];

        $searchParams = $searchService->createMySQLSearchParams(filters: $filters);
        $searchConditions = $searchService->createMySQLSearchConditions(filters: $filters, fieldsToSearch:  $fieldsToSearch);
        $filters = $searchService->unsetSpecialQueryParams(filters: $filters);

        return new JSONResponse(['results' => $this->synchronizationMapper->findAll(limit: null, offset: null, filters: $filters, searchConditions: $searchConditions, searchParams: $searchParams)]);
    }

    /**
     * Retrieves a single synchronization by its ID
     *
     * This method returns a JSON response containing the details of a specific synchronization.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $id The ID of the synchronization to retrieve
     * @return JSONResponse A JSON response containing the synchronization details
     */
    public function show(string $id): JSONResponse
    {
        try {
            return new JSONResponse($this->synchronizationMapper->find(id: (int) $id));
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }
    }

    /**
     * Creates a new synchronization
     *
     * This method creates a new synchronization based on POST data.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the created synchronization
     */
    public function create(): JSONResponse
    {
        $data = $this->request->getParams();

        foreach ($data as $key => $value) {
            if (str_starts_with($key, '_')) {
                unset($data[$key]);
            }
        }

        if (isset($data['id'])) {
            unset($data['id']);
        }

        return new JSONResponse($this->synchronizationMapper->createFromArray(object: $data));
    }

    /**
     * Updates an existing synchronization
     *
     * This method updates an existing synchronization based on its ID.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $id The ID of the synchronization to update
     * @return JSONResponse A JSON response containing the updated synchronization details
     */
    public function update(int $id): JSONResponse
    {
        $data = $this->request->getParams();

        foreach ($data as $key => $value) {
            if (str_starts_with($key, '_')) {
                unset($data[$key]);
            }
        }
        if (isset($data['id'])) {
            unset($data['id']);
        }
        return new JSONResponse($this->synchronizationMapper->updateFromArray(id: (int) $id, object: $data));
    }

    /**
     * Deletes a synchronization
     *
     * This method deletes a synchronization based on its ID.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $id The ID of the synchronization to delete
     * @return JSONResponse An empty JSON response
     */
    public function destroy(int $id): JSONResponse
    {
        $this->synchronizationMapper->delete($this->synchronizationMapper->find((int) $id));

        return new JSONResponse([]);
    }

    /**
     * Retrieves call logs for a job
     *
     * This method returns all the call logs associated with a source based on its ID.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param int $id The ID of the source to retrieve logs for
     * @return JSONResponse A JSON response containing the call logs
     */
    public function contracts(int $id): JSONResponse
    {
        try {
            $contracts = $this->synchronizationContractMapper->findAll(null, null, ['synchronization_id' => $id]);
            return new JSONResponse($contracts);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Contracts not found'], 404);
        }
    }

    /**
     * Retrieves synchronization logs with filtering and pagination support
     *
     * This method returns synchronization logs based on query parameters,
     * with support for various filtering parameters to narrow down the results.
     *
     * Query Parameters:
     * - synchronization_id: Filter logs by synchronization ID
     * - date_from: Filter logs created after this date
     * - date_to: Filter logs created before this date
     * - status: Filter logs by status
     * - slow_syncs: Filter logs with sync time > 5000ms
     * - limit: Number of results per page (default: 20)
     * - offset: Offset for pagination (default: 0)
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the filtered synchronization logs and pagination
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
            if (!empty($filters['status'])) {
                $specialFilters['status'] = $filters['status'];
            }
            if (!empty($filters['slow_syncs'])) {
                $specialFilters['slow_syncs'] = 5000; // 5 seconds in milliseconds
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

            if (!empty($specialFilters['status'])) {
                $searchConditions[] = "status = ?";
                $searchParams[] = $specialFilters['status'];
            }

            if (!empty($specialFilters['slow_syncs'])) {
                $searchConditions[] = "sync_time > ?";
                $searchParams[] = $specialFilters['slow_syncs'];
            }

            // Remove special query params from filters
            $filters = $searchService->unsetSpecialQueryParams(filters: $filters);

            // Get synchronization logs with filters and pagination
            $syncLogs = $this->synchronizationLogMapper->findAll(
                limit: $limit,
                offset: $offset,
                filters: $filters,
                searchConditions: $searchConditions,
                searchParams: $searchParams
            );

            // Get total count for pagination
            $total = $this->synchronizationLogMapper->getTotalCount($filters);
            $pages = $limit > 0 ? ceil($total / $limit) : 1;
            $currentPage = $limit > 0 ? floor($offset / $limit) + 1 : 1;

            // Return flattened paginated response
            return new JSONResponse([
                'results' => $syncLogs,
                'page' => $currentPage,
                'pages' => $pages,
                'results_count' => count($syncLogs),
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return new JSONResponse(['error' => 'Failed to retrieve logs: ' . $e->getMessage()], 500);
        }
    }

	/**
	 * Tests a synchronization
	 *
	 * This method tests a synchronization without persisting anything to the database.
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id The ID of the synchronization
	 * @param bool|null $force Whether to force synchronization regardless of changes (default: false)
	 *
	 * @return JSONResponse A JSON response containing the test results
	 * @throws GuzzleException
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 *
	 * @example
	 * Request:
	 * POST with optional force parameter
	 *
	 * Response:
	 * {
	 *     "resultObject": {
	 *         "fullName": "John Doe",
	 *         "userAge": 30,
	 *         "contactEmail": "john@example.com"
	 *     },
	 *     "isValid": true,
	 *     "validationErrors": []
	 * }
	 */
    public function test(int $id, ?bool $force = false): JSONResponse
    {
        try {
            $synchronization = $this->synchronizationMapper->find(id: $id);
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

        // Try to synchronize
        try {
            $logAndContractArray = $this->synchronizationService->synchronize(
                synchronization: $synchronization,
                isTest: true,
                force: $force
            );

            // Return the result as a JSON response
            return new JSONResponse(data: $logAndContractArray, statusCode: 200);
        } catch (Exception $e) {
            // Check if getHeaders method exists and use it if available
            $headers = method_exists($e, 'getHeaders') ? $e->getHeaders() : [];

            // If synchronization fails, return an error response
            return new JSONResponse(
                data: [
                    'error' => 'Synchronization error',
                    'message' => $e->getMessage()
                ],
                statusCode: $e->getCode() ?? 400,
                headers: $headers
            );
        }
    }

    /**
     * Run a synchronization
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * Endpoint: /api/synchronizations-run/{id}
     *
     * @param int $id The ID of the synchronization to run
     *
     * @return JSONResponse A JSON response containing the run results
     * @throws GuzzleException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(int $id): JSONResponse
    {
        $parameters = $this->request->getParams();
        $test  = filter_var($parameters['test'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $force = filter_var($parameters['force'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $source = $parameters['source'] ?? null;
        $data = $parameters['data'] ?? [];

        try {
            $synchronization = $this->synchronizationMapper->find(id: $id);
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

        // Try to synchronize
        try {
            $logAndContractArray = $this->synchronizationService->synchronize(
                synchronization: $synchronization,
                isTest: $test,
                force: $force,
                source: $source,
                data: $data
            );

            // Return the result as a JSON response
            return new JSONResponse(data: $logAndContractArray, statusCode: 200);
        } catch (Exception $e) {
            // Check if getHeaders method exists and use it if available
            $headers = method_exists($e, 'getHeaders') ? $e->getHeaders() : [];

            // If synchronization fails, return an error response
            return new JSONResponse(
                data: [
                    'error' => 'Synchronization error',
                    'message' => $e->getMessage()
                ],
                statusCode: 400,
                headers: $headers
            );
        }
    }

    /**
     * Deletes a single synchronization log
     *
     * This method deletes a synchronization log based on its ID.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param int $id The ID of the synchronization log to delete
     * @return JSONResponse A JSON response indicating success or failure
     */
    public function deleteLog(int $id): JSONResponse
    {
        try {
            $log = $this->synchronizationLogMapper->find($id);
            $this->synchronizationLogMapper->delete($log);
            
            return new JSONResponse(['message' => 'Log deleted successfully'], 200);
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(['error' => 'Log not found'], 404);
        } catch (\Exception $exception) {
            return new JSONResponse(['error' => 'Failed to delete log: ' . $exception->getMessage()], 500);
        }
    }


}
