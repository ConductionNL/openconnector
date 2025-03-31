<?php
/**
 * OpenConnector Sources Controller
 *
 * This file contains the controller for handling source related operations
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

use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\CallLogMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Controller for handling source related operations
 */
class SourcesController extends Controller
{


    /**
     * Constructor for the SourcesController
     *
     * @param string        $appName       The name of the app
     * @param IRequest      $request       The request object
     * @param IAppConfig    $config        The app configuration object
     * @param SourceMapper  $sourceMapper  The source mapper object
     * @param CallLogMapper $callLogMapper The call log mapper object
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
        private readonly IAppConfig $config,
        private readonly SourceMapper $sourceMapper,
        private readonly CallLogMapper $callLogMapper
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
     * Retrieves a list of all sources
     *
     * This method returns a JSON response containing an array of all sources in the system.
     *
     * @param ObjectService $objectService Service for object operations
     * @param SearchService $searchService Service for search operations
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the list of sources
     */
    public function index(ObjectService $objectService, SearchService $searchService): JSONResponse
    {
        $filters        = $this->request->getParams();
        $fieldsToSearch = [
            'name',
            'description',
        ];

        $searchParams     = $searchService->createMySQLSearchParams(filters: $filters);
        $searchConditions = $searchService->createMySQLSearchConditions(filters: $filters, fieldsToSearch: $fieldsToSearch);
        $filters          = $searchService->unsetSpecialQueryParams(filters: $filters);

        return new JSONResponse(
                [
                    'results' => $this->sourceMapper->findAll(
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
     * Retrieves a single source by its ID
     *
     * This method returns a JSON response containing the details of a specific source.
     *
     * @param string $id The ID of the source to retrieve
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the source details
     */
    public function show(string $id): JSONResponse
    {
        try {
            return new JSONResponse($this->sourceMapper->find(id: (int) $id));
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

    }//end show()


    /**
     * Creates a new source
     *
     * This method creates a new source based on POST data.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the created source
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

        return new JSONResponse($this->sourceMapper->createFromArray(object: $data));

    }//end create()


    /**
     * Updates an existing source
     *
     * This method updates an existing source based on its ID.
     *
     * @param int $id The ID of the source to update
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the updated source details
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

        return new JSONResponse($this->sourceMapper->updateFromArray(id: (int) $id, object: $data));

    }//end update()


    /**
     * Deletes a source
     *
     * This method deletes a source based on its ID.
     *
     * @param int $id The ID of the source to delete
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse An empty JSON response
     */
    public function destroy(int $id): JSONResponse
    {
        $this->sourceMapper->delete($this->sourceMapper->find((int) $id));

        return new JSONResponse([]);

    }//end destroy()


    /**
     * Retrieves call logs for a source
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
    public function logs(int $id): JSONResponse
    {
        try {
            $callLogs = $this->callLogMapper->findAll(null, null, ['source_id' => $id]);
            return new JSONResponse($callLogs);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Source not found'], 404);
        }

    }//end logs()


    /**
     * Test a source
     *
     * This method fires a test call to the source and returns the response.
     *
     * @param CallService $callService Service for making API calls
     * @param int         $id          The ID of the source to test
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the test results
     */
    public function test(CallService $callService, int $id): JSONResponse
    {
        // Get the source.
        try {
            $source = $this->sourceMapper->find(id: (int) $id);
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

        // Get the request data.
        $requestData = $this->request->getParams();

        // Build Guzzle call configuration array.
        $config = [];

        // Add headers if present.
        if (isset($requestData['headers']) === true && is_array($requestData['headers']) === true) {
            $config['headers'] = $requestData['headers'];
        }

        // Add query parameters if present.
        if (isset($requestData['query']) === true && is_array($requestData['query']) === true) {
            $config['query'] = $requestData['query'];
        }

        // Set method, default to POST if not provided.
        $method = ($requestData['method'] ?? 'GET');

        // Set endpoint.
        $endpoint = ($requestData['endpoint'] ?? '');

        // Set body if present.
        if (isset($requestData['body']) === true) {
            $config['body'] = $requestData['body'];
        }

        // Set content type based on the type parameter.
        if (isset($requestData['type']) === true) {
            switch ($requestData['type']) {
                case 'json':
                    $config['headers']['Content-Type'] = 'application/json';
                    break;
                case 'xml':
                    $config['headers']['Content-Type'] = 'application/xml';
                    break;
                case 'yaml':
                    $config['headers']['Content-Type'] = 'application/x-yaml';
                    break;
            }
        }

        // Fire the call.
        $timeStart = microtime(true);
        $callLog   = $callService->call($source, $endpoint, $method, $config);
        $timeEnd   = microtime(true);

        return new JSONResponse($callLog->jsonSerialize());

    }//end test()


}//end class
