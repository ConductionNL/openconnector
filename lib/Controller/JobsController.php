<?php
/**
 * OpenConnector Jobs Controller
 *
 * This file contains the controller for handling job related operations
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
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Db\Job;
use OCA\OpenConnector\Db\JobMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\BackgroundJob\IJobList;
use OCA\OpenConnector\Db\JobLogMapper;
use OCA\OpenConnector\Service\JobService;
use OCP\AppFramework\Db\DoesNotExistException;

use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Db\SynchronizationMapper;

/**
 * Controller for handling job related operations
 */
class JobsController extends Controller
{


    /**
     * Constructor for the JobController
     *
     * @param string                 $appName                The name of the app
     * @param IRequest               $request                The request object
     * @param IAppConfig             $config                 The app configuration object
     * @param JobMapper              $jobMapper              Job mapper for database operations
     * @param JobLogMapper           $jobLogMapper           Job log mapper for database operations
     * @param JobService             $jobService             Service for handling job operations
     * @param IJobList               $jobList                Job list for background jobs
     * @param SynchronizationService $synchronizationService Service for synchronization operations
     * @param SynchronizationMapper  $synchronizationMapper  Mapper for synchronization operations
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
        private readonly IAppConfig $config,
        private readonly JobMapper $jobMapper,
        private readonly JobLogMapper $jobLogMapper,
        private readonly JobService $jobService,
        private readonly IJobList $jobList,
        private readonly SynchronizationService $synchronizationService,
        private readonly SynchronizationMapper $synchronizationMapper
    ) {
        parent::__construct($appName, $request);
        $this->jobList = $jobList;

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
     * Retrieves a list of all jobs
     *
     * This method returns a JSON response containing an array of all jobs in the system.
     *
     * @param ObjectService $objectService Service for object operations
     * @param SearchService $searchService Service for search operations
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the list of jobs
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
                    'results' => $this->jobMapper->findAll(
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
     * Retrieves a single job by its ID
     *
     * This method returns a JSON response containing the details of a specific job.
     *
     * @param string $id The ID of the job to retrieve
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the job details
     */
    public function show(string $id): JSONResponse
    {
        try {
            return new JSONResponse($this->jobMapper->find(id: (int) $id));
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

    }//end show()


    /**
     * Creates a new job
     *
     * This method creates a new job based on POST data.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the created job
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

        // Create the job.
        $job = $this->jobMapper->createFromArray(object: $data);
        // Let's schedule the job.
        $job = $this->jobService->scheduleJob($job);

        return new JSONResponse($job);

    }//end create()


    /**
     * Updates an existing job
     *
     * This method updates an existing job based on its ID.
     *
     * @param int $id The ID of the job to update
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the updated job details
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

        // Create the job.
        $job = $this->jobMapper->updateFromArray(id: (int) $id, object: $data);
        // Let's schedule the job.
        $job = $this->jobService->scheduleJob($job);

        return new JSONResponse($job);

    }//end update()


    /**
     * Deletes a job
     *
     * This method deletes a job based on its ID.
     *
     * @param int $id The ID of the job to delete
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse An empty JSON response
     */
    public function destroy(int $id): JSONResponse
    {
        $this->jobMapper->delete($this->jobMapper->find((int) $id));

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
    public function logs(int $id): JSONResponse
    {
        try {
            $jobLogs = $this->jobLogMapper->findAll(null, null, ['job_id' => $id]);
            return new JSONResponse($jobLogs);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(['error' => 'Job not found'], 404);
        }

    }//end logs()


    /**
     * Test a job
     *
     * This method fires a test call to the job and returns the response.
     *
     * @param int $id The ID of the job to test
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the test results
     */
    public function run(int $id): JSONResponse
    {
        try {
            $job = $this->jobMapper->find(id: $id);
        } catch (DoesNotExistException $exception) {
            return new JSONResponse(data: ['error' => 'Not Found'], statusCode: 404);
        }

        if ($job->getJobListId() === false) {
            return new JSONResponse(data: ['error' => 'Job not scheduled'], statusCode: 404);
        }

        try {
            $job = $this->jobList->getById($job->getJobListId());
            if ($job !== null) {
                $job->setArgument(['jobId' => $id, 'forceRun' => true]);
                $job->start($this->jobList);

                $lastLog = $this->jobLogMapper->getLastCallLog();
                if ($lastLog !== null && ($lastLog->getJobId() === null || (int) $lastLog->getJobId() === $id)) {
                    return new JSONResponse(data: $lastLog, statusCode: 200);
                }
            }

            return new JSONResponse(
                data: ['error' => 'No job log could be found, job did not go successfully or failed to log anything'],
                statusCode: 500
            );
        } catch (Exception $exception) {
            return new JSONResponse(data: ['error' => $exception->getMessage()], statusCode: 400);
        }

    }//end run()


}//end class
