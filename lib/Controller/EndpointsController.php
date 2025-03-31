<?php
/**
 * OpenConnector Endpoints Controller
 *
 * This file contains the controller for handling endpoint related operations
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

use Exception;
use OCA\OpenConnector\Http\XMLResponse;
use OCA\OpenConnector\Service\AuthorizationService;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\EndpointService;
use OCA\OpenConnector\Db\EndpointMapper;
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
     * @var integer
     */
    private int $corsMaxAge;


    /**
     * Constructor for the EndpointsController
     *
     * @param string               $appName              The name of the app
     * @param IRequest             $request              The request object
     * @param IAppConfig           $config               The app configuration object
     * @param EndpointMapper       $endpointMapper       The endpoint mapper object
     * @param EndpointService      $endpointService      Service for handling endpoint operations
     * @param AuthorizationService $authorizationService Service for handling authorization
     * @param string               $corsMethods          CORS allowed methods
     * @param string               $corsAllowedHeaders   CORS allowed headers
     * @param int                  $corsMaxAge           CORS max age in seconds
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
        private IAppConfig $config,
        private EndpointMapper $endpointMapper,
        private EndpointService $endpointService,
        private AuthorizationService $authorizationService,
        $corsMethods='PUT, POST, GET, DELETE, PATCH',
        $corsAllowedHeaders='Authorization, Content-Type, Accept',
        $corsMaxAge=1728000
    ) {
        parent::__construct($appName, $request);
        $this->corsMethods        = $corsMethods;
        $this->corsAllowedHeaders = $corsAllowedHeaders;
        $this->corsMaxAge         = $corsMaxAge;

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
     * Retrieves a list of all endpoints
     *
     * This method returns a JSON response containing an array of all endpoints in the system.
     *
     * @param ObjectService $objectService Service for object operations
     * @param SearchService $searchService Service for search operations
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the list of endpoints
     */
    public function index(ObjectService $objectService, SearchService $searchService): JSONResponse
    {
        $filters        = $this->request->getParams();
        $fieldsToSearch = [
            'name',
            'description',
            'endpoint',
        ];

        $searchParams     = $searchService->createMySQLSearchParams(filters: $filters);
        $searchConditions = $searchService->createMySQLSearchConditions(
                filters: $filters,
                fieldsToSearch: $fieldsToSearch
        );
        $filters          = $searchService->unsetSpecialQueryParams(filters: $filters);

        return new JSONResponse(
                [
                    'results' => $this->endpointMapper->findAll(
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
     * Retrieves a single endpoint by its ID
     *
     * This method returns a JSON response containing the details of a specific endpoint.
     *
     * @param string $id The ID of the endpoint to retrieve
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the endpoint details
     */
    public function show(string $id): JSONResponse
    {
        try {
            return new JSONResponse($this->endpointMapper->find(id: (int) $id));
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

    }//end show()


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

    }//end create()


    /**
     * Updates an existing endpoint
     *
     * This method updates an existing endpoint based on its ID.
     *
     * @param int $id The ID of the endpoint to update
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
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

        $endpoint = $this->endpointMapper->updateFromArray(id: (int) $id, object: $data);

        return new JSONResponse($endpoint);

    }//end update()


    /**
     * Deletes an endpoint
     *
     * This method deletes an endpoint based on its ID.
     *
     * @param int $id The ID of the endpoint to delete
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse An empty JSON response
     * @throws \OCP\DB\Exception
     */
    public function destroy(int $id): JSONResponse
    {
        $this->endpointMapper->delete($this->endpointMapper->find((int) $id));

        return new JSONResponse([]);

    }//end destroy()


    /**
     * Handles generic path requests by matching against registered endpoints
     *
     * This method checks if the current path matches any registered endpoint patterns
     * and forwards the request to the appropriate endpoint service if found.
     *
     * @param string $_path The path to handle
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     *
     * @return JSONResponse|XMLResponse The response from the endpoint service or 404 if no match
     * @throws Exception
     */
    public function handlePath(string $_path): Response
    {
        // Find matching endpoints for the given path and method.
        $matchingEndpoints = $this->endpointMapper->findByPathRegex(
                path: $_path,
                method: $this->request->getMethod()
        );

        // If no matching endpoints found, return 404.
        if (empty($matchingEndpoints) === true) {
            return new JSONResponse(
                data: ['error' => 'No matching endpoint found for path and method: '.$_path.' '.$this->request->getMethod()],
                statusCode: 404
            );
        }

        // If multiple matching endpoints found, return 409.
        if (count($matchingEndpoints) > 1) {
            return new JSONResponse(
                data: ['error' => 'Multiple endpoints found for path and method: '.$_path.' '.$this->request->getMethod()],
                statusCode: 409
            );
        }

        // Get the first matching endpoint since we have already filtered by method.
        $endpoint = reset($matchingEndpoints);

        // Forward the request to the endpoint service.
        $response = $this->endpointService->handleRequest($endpoint, $this->request, $_path);

        // Check if the Accept header is set to XML.
        $acceptHeader = $this->request->getHeader('Accept');
        if (stripos($acceptHeader, 'application/xml') !== false) {
            // Convert JSON response to XML response.
            $response = new XMLResponse($response->getData(), $response->getStatus(), $response->getHeaders(), $_path);
        }

        return $this->authorizationService->corsAfterController($this->request, $response);

    }//end handlePath()


    /*
     * Handles CORS preflight requests by setting appropriate headers
     *
     * This method handles OPTIONS preflight requests for Cross-Origin Resource Sharing.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     * @since           7.0.0
     *
     * @return Response The CORS response
     */
    public function preflightedCors(): Response
    {
        // Determine the origin.
        $origin = '*';
        if (isset($this->request->server['HTTP_ORIGIN']) === true) {
            $origin = $this->request->server['HTTP_ORIGIN'];
        }

        // Create and configure the response.
        $response = new Response();
        $response->addHeader('Access-Control-Allow-Origin', $origin);
        $response->addHeader('Access-Control-Allow-Methods', $this->corsMethods);
        $response->addHeader('Access-Control-Max-Age', (string) $this->corsMaxAge);
        $response->addHeader('Access-Control-Allow-Headers', $this->corsAllowedHeaders);
        $response->addHeader('Access-Control-Allow-Credentials', 'false');

        return $response;

    }//end preflightedCors()


}//end class
