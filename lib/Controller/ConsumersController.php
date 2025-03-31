<?php
/**
 * OpenConnector Consumers Controller
 *
 * This file contains the controller for handling consumer related operations
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
use OCA\OpenConnector\Db\Consumer;
use OCA\OpenConnector\Db\ConsumerMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\AppFramework\Db\DoesNotExistException;

/**
 * Controller for handling consumer related operations
 */
class ConsumersController extends Controller
{


    /**
     * Constructor for the ConsumerController
     *
     * @param string         $appName        The name of the app
     * @param IRequest       $request        The request object
     * @param IAppConfig     $config         The app configuration object
     * @param ConsumerMapper $consumerMapper The consumer mapper object
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
    private IAppConfig $config,
    private ConsumerMapper $consumerMapper
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
     * Retrieves a list of all consumers
     *
     * This method returns a JSON response containing an array of all consumers in the system.
     *
     * @param ObjectService $objectService Service for object operations
     * @param SearchService $searchService Service for search operations
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the list of consumers
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
                'results' => $this->consumerMapper->findAll(
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
     * Retrieves a single consumer by its ID
     *
     * This method returns a JSON response containing the details of a specific consumer.
     *
     * @param string $id The ID of the consumer to retrieve
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the consumer details
     */
    public function show(string $id): JSONResponse
    {
        try {
            return new JSONResponse($this->consumerMapper->find(id: (int) $id));
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

    }//end show()


    /**
     * Creates a new consumer
     *
     * This method creates a new consumer based on POST data.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the created consumer
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

        // Create the consumer.
        $consumer = $this->consumerMapper->createFromArray(object: $data);

        return new JSONResponse($consumer);

    }//end create()


    /**
     * Updates an existing consumer
     *
     * This method updates an existing consumer based on its ID.
     *
     * @param int $id The ID of the consumer to update
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the updated consumer details
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

        // Update the consumer.
        $consumer = $this->consumerMapper->updateFromArray(id: (int) $id, object: $data);

        return new JSONResponse($consumer);

    }//end update()


    /**
     * Deletes a consumer
     *
     * This method deletes a consumer based on its ID.
     *
     * @param int $id The ID of the consumer to delete
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse An empty JSON response
     */
    public function destroy(int $id): JSONResponse
    {
        $this->consumerMapper->delete($this->consumerMapper->find((int) $id));

        return new JSONResponse([]);

    }//end destroy()


}//end class
