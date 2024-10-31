<?php

namespace OCA\OpenConnector\Controller;

use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Service\MappingService;
use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;

class MappingsController extends Controller
{
    /**
     * Constructor for the MappingsController
     *
     * @param string $appName The name of the app
     * @param IRequest $request The request object
     * @param IAppConfig $config The app configuration object
     */
    public function __construct(
        $appName,
        IRequest $request,
        private readonly IAppConfig $config,
        private readonly MappingMapper $mappingMapper,
        private readonly MappingService $mappingService
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
     * Retrieves a list of all mappings
     *
     * This method returns a JSON response containing an array of all mappings in the system.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the list of mappings
     */
    public function index(ObjectService $objectService, SearchService $searchService): JSONResponse
    {
        $filters = $this->request->getParams();
        $fieldsToSearch = ['name', 'description'];

        $searchParams = $searchService->createMySQLSearchParams(filters: $filters);
        $searchConditions = $searchService->createMySQLSearchConditions(filters: $filters, fieldsToSearch:  $fieldsToSearch);
        $filters = $searchService->unsetSpecialQueryParams(filters: $filters);

        return new JSONResponse(['results' => $this->mappingMapper->findAll(limit: null, offset: null, filters: $filters, searchConditions: $searchConditions, searchParams: $searchParams)]);
    }

    /**
     * Retrieves a single mapping by its ID
     *
     * This method returns a JSON response containing the details of a specific mapping.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $id The ID of the mapping to retrieve
     * @return JSONResponse A JSON response containing the mapping details
     */
    public function show(string $id): JSONResponse
    {
        try {
            return new JSONResponse($this->mappingMapper->find(id: (int) $id));
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }
    }

    /**
     * Creates a new mapping
     *
     * This method creates a new mapping based on POST data.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the created mapping
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

        return new JSONResponse($this->mappingMapper->createFromArray(object: $data));
    }

    /**
     * Updates an existing mapping
     *
     * This method updates an existing mapping based on its ID.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $id The ID of the mapping to update
     * @return JSONResponse A JSON response containing the updated mapping details
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
        return new JSONResponse($this->mappingMapper->updateFromArray(id: (int) $id, object: $data));
    }

    /**
     * Deletes a mapping
     *
     * This method deletes a mapping based on its ID.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param string $id The ID of the mapping to delete
     * @return JSONResponse An empty JSON response
     */
    public function destroy(int $id): JSONResponse
    {
        $this->mappingMapper->delete($this->mappingMapper->find((int) $id));

        return new JSONResponse([]);
    }

    /**
     * Tests a mapping
     *
     * This method tests a mapping with provided input data and optional schema validation.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the test results
     *
     * @example
     * Request:
     * {
     *     "inputObject": "{\"name\":\"John Doe\",\"age\":30,\"email\":\"john@example.com\"}",
     *     "mapping": {
	 * 			"mapping": {
	 * 				"fullName":"{{name}}",
	 * 				"userAge":"{{age}}",
	 * 				"contactEmail":"{{email}}"
	 * 			}
	 * 	   },
     *     "schema": "user_schema_id",
     *     "validation": true
     * }
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
    public function test(): JSONResponse
    {
        // Get all parameters from the request
        $data = $this->request->getParams();


        // Validate that required parameters are present
        if (!isset($data['inputObject']) || !isset($data['mapping'])) {
            throw new \InvalidArgumentException('Both `inputObject` and `mapping` are required');
        }

        // Decode the input object from JSON
        $inputObject = $data['inputObject'];

        // Decode the mapping from JSON
		$mapping = $data['mapping'];

        // Initialize schema and validation flags
        $schema = false;
        $validation = false;

        // If a schema is provided, retrieve it
        if (isset($data['schema']) && !empty($data['schema'])) {
            $schemaId = $data['schema'];
            $schema = $this->objectService->getObject($schemaId);
        }

        // Check if validation is requested
        if (isset($data['validation']) && !empty($data['validation'])) {
            $validation = $data['validation'];
        }

        // Create a new Mapping object and hydrate it with the provided mapping
        $mappingObject = new Mapping();
        $mappingObject->hydrate($mapping);

        // Perform the mapping operation
        try {
            // var_dump($mappingObject->getMapping()['title']);
            // var_dump($inputObject['values']['case.subject_external']);
            $resultObject = $this->mappingService->executeMapping(mapping: $mappingObject, input: $inputObject);
        } catch (\Exception $e) {
            // If mapping fails, return an error response
            return new JSONResponse([
                'error' => 'Mapping error',
                'message' => $e->getMessage()
            ], 400);
        }

        // Initialize validation variables
        $isValid = true;
        $validationErrors = [];

        // Perform schema validation if both schema and validation are provided
        if ($schema !== false && $validation !== false) {
            // TODO: Implement schema validation logic here
            // For now, we'll just assume it's always valid
            $isValid = true;
        }

        // Return the result as a JSON response
        return new JSONResponse([
            'resultObject' => $resultObject,
            'isValid' => $isValid,
            'validationErrors' => $validationErrors
        ]);
    }
}
