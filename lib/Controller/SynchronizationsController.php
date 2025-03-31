<?php
/**
 * OpenConnector Synchronizations Controller
 *
 * This file contains the controller for handling synchronization related operations
 * in the OpenConnector application.
 *
 * @category  Controller
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

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

/**
 * Controller for handling synchronization related operations
 */
class SynchronizationsController extends Controller
{


    /**
     * Constructor for the SynchronizationsController
     *
     * @param string                        $appName                       The name of the app
     * @param IRequest                      $request                       The request object
     * @param IAppConfig                    $config                        The app configuration object
     * @param SynchronizationMapper         $synchronizationMapper         Mapper for synchronization operations
     * @param SynchronizationContractMapper $synchronizationContractMapper Mapper for synchronization contract operations
     * @param SynchronizationLogMapper      $synchronizationLogMapper      Mapper for synchronization log operations
     * @param SynchronizationService        $synchronizationService        Service for synchronization operations
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
        private readonly IAppConfig $config,
        private readonly SynchronizationMapper $synchronizationMapper,
        private readonly SynchronizationContractMapper $synchronizationContractMapper,
        private readonly SynchronizationLogMapper $synchronizationLogMapper,
        private readonly SynchronizationService $synchronizationService
    ) {
        parent::__construct($appName, $request);

    }//end __construct()


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

    }//end page()


    /**
     * Retrieves a list of all synchronizations
     *
     * This method returns a JSON response containing an array of all synchronizations in the system.
     *
     * @param ObjectService $objectService Service for object operations
     * @param SearchService $searchService Service for search operations
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the list of synchronizations
     */
    public function index(ObjectService $objectService, SearchService $searchService): JSONResponse
    {
        $filters        = $this->request->getParams();
        $fieldsToSearch = [
            'name',
            'description',
        ];

        $searchParams     = $searchService->createMySQLSearchParams(filters: $filters);
        $searchConditions = $searchService->createMySQLSearchConditions(
                filters: $filters,
                fieldsToSearch: $fieldsToSearch
        );
        $filters          = $searchService->unsetSpecialQueryParams(filters: $filters);

        return new JSONResponse(
                [
                    'results' => $this->synchronizationMapper->findAll(
                        limit: null,
                        offset: null,
                        filters: $filters,
                        searchConditions: $searchConditions,
                        searchParams: $searchParams
                ),
                ]
        );

    }//end index()


    /**
     * Retrieves a single synchronization by its ID
     *
     * This method returns a JSON response containing the details of a specific synchronization.
     *
     * @param string $id The ID of the synchronization to retrieve
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the synchronization details
     */
    public function show(string $id): JSONResponse
    {
        try {
            return new JSONResponse($this->synchronizationMapper->find(id: (int) $id));
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

    }//end show()


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
            if (str_starts_with($key, '_') === true) {
                unset($data[$key]);
            }
        }

        if (isset($data['id']) === true) {
            unset($data['id']);
        }

        return new JSONResponse($this->synchronizationMapper->createFromArray(object: $data));

    }//end create()


    /**
     * Updates an existing synchronization
     *
     * This method updates an existing synchronization based on its ID.
     *
     * @param int $id The ID of the synchronization to update
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the updated synchronization details
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

        return new JSONResponse($this->synchronizationMapper->updateFromArray(id: (int) $id, object: $data));

    }//end update()


    /**
     * Deletes a synchronization
     *
     * This method deletes a synchronization based on its ID.
     *
     * @param int $id The ID of the synchronization to delete
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse An empty JSON response
     */
    public function destroy(int $id): JSONResponse
    {
        $this->synchronizationMapper->delete($this->synchronizationMapper->find((int) $id));

        return new JSONResponse([]);

    }//end destroy()


    /**
     * Retrieves call logs for a job
     *
     * This method returns all the call logs associated with a source based on its ID.
     *
     * @param int $id The ID of the source to retrieve logs for
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
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

    }//end contracts()


    /**
     * Retrieves call logs for a job
     *
     * This method returns all the call logs associated with a source based on its ID.
     *
     * @param string $uuid The UUID of the synchronization to retrieve logs for
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the call logs
     */
    public function logs(string $uuid): JSONResponse
    {
        try {
            $logs = $this->synchronizationLogMapper->findAll(null, null, ['synchronization_id' => $uuid]);
            return new JSONResponse($logs);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Logs not found'], 404);
        }

    }//end logs()


    /**
     * Tests a synchronization
     *
     * This method tests a synchronization without persisting anything to the database.
     *
     * @param int       $id    The ID of the synchronization
     * @param bool|null $force Whether to force synchronization regardless of changes (default: false)
     *
     * @NoAdminRequired
     * @NoCSRFRequired
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
    public function test(int $id, ?bool $force=false): JSONResponse
    {
        try {
            $synchronization = $this->synchronizationMapper->find(id: $id);
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

        // Try to synchronize.
        try {
            $logAndContractArray = $this->synchronizationService->synchronize(
                synchronization: $synchronization,
                isTest: true,
                force: $force
            );

            // Return the result as a JSON response.
            return new JSONResponse(data: $logAndContractArray, statusCode: 200);
        } catch (Exception $e) {
            // Check if getHeaders method exists and use it if available.
            $headers = [];
            if (method_exists($e, 'getHeaders') === true) {
                $headers = $e->getHeaders();
            }

            // If synchronization fails, return an error response.
            return new JSONResponse(
                data: [
                    'error'   => 'Synchronization error',
                    'message' => $e->getMessage(),
                ],
                statusCode: $this->getErrorStatusCode($e),
                headers: $headers
            );
        }//end try

    }//end test()


    /**
     * Get the appropriate error status code from an exception
     *
     * @param Exception $e The exception to extract error code from
     *
     * @return int The HTTP status code to use
     */
    private function getErrorStatusCode(Exception $e): int
    {
        if ($e->getCode() !== 0) {
            return $e->getCode();
        }

        return 400;

    }//end getErrorStatusCode()


    /**
     * Run a synchronization
     *
     * This method runs a synchronization and persists the results to the database.
     *
     * @param int $id The ID of the synchronization to run
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the run results
     * @throws GuzzleException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(int $id): JSONResponse
    {
        $parameters = $this->request->getParams();
        $test       = (isset($parameters['test']) && filter_var($parameters['test'], FILTER_VALIDATE_BOOLEAN) === true);
        $force      = (isset($parameters['force']) && filter_var($parameters['force'], FILTER_VALIDATE_BOOLEAN) === true);

        try {
            $synchronization = $this->synchronizationMapper->find(id: $id);
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

        // Try to synchronize.
        try {
            $logAndContractArray = $this->synchronizationService->synchronize(
                synchronization: $synchronization,
                isTest: $test,
                force: $force
            );

            // Return the result as a JSON response.
            return new JSONResponse(data: $logAndContractArray, statusCode: 200);
        } catch (Exception $e) {
            // Check if getHeaders method exists and use it if available.
            $headers = [];
            if (method_exists($e, 'getHeaders') === true) {
                $headers = $e->getHeaders();
            }

            // If synchronization fails, return an error response.
            return new JSONResponse(
                data: [
                    'error'   => 'Synchronization error',
                    'message' => $e->getMessage(),
                ],
                statusCode: $this->getErrorStatusCode($e),
                headers: $headers
            );
        }//end try

    }//end run()


}//end class
