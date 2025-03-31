<?php
/**
 * OpenConnector Dashboard Controller
 *
 * This file contains the controller for handling dashboard related operations
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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Db\ConsumerMapper;
use OCA\OpenConnector\Db\EndpointMapper;
use OCA\OpenConnector\Db\JobMapper;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\CallLogMapper;
use OCA\OpenConnector\Db\JobLogMapper;
use OCA\OpenConnector\Db\SynchronizationContractLogMapper;

/**
 * Dashboard Controller
 *
 * Handles requests related to the dashboard view, including statistics and overview data
 *
 * @package   OCA\OpenConnector\Controller
 * @category  Controller
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 * @link      https://OpenConnector.app
 */
class DashboardController extends Controller
{


    /**
     * Constructor for DashboardController
     *
     * @param string                           $appName                          The name of the app
     * @param IRequest                         $request                          The request object
     * @param SynchronizationMapper            $synchronizationMapper            Synchronization mapper
     * @param SourceMapper                     $sourceMapper                     Source mapper
     * @param SynchronizationContractMapper    $synchronizationContractMapper    Synchronization contract mapper
     * @param ConsumerMapper                   $consumerMapper                   Consumer mapper
     * @param EndpointMapper                   $endpointMapper                   Endpoint mapper
     * @param JobMapper                        $jobMapper                        Job mapper
     * @param MappingMapper                    $mappingMapper                    Mapping mapper
     * @param CallLogMapper                    $callLogMapper                    Call log mapper
     * @param JobLogMapper                     $jobLogMapper                     Job log mapper
     * @param SynchronizationContractLogMapper $synchronizationContractLogMapper Synchronization contract log mapper
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
        private readonly SynchronizationMapper $synchronizationMapper,
        private readonly SourceMapper $sourceMapper,
        private readonly SynchronizationContractMapper $synchronizationContractMapper,
        private readonly ConsumerMapper $consumerMapper,
        private readonly EndpointMapper $endpointMapper,
        private readonly JobMapper $jobMapper,
        private readonly MappingMapper $mappingMapper,
        private readonly CallLogMapper $callLogMapper,
        private readonly JobLogMapper $jobLogMapper,
        private readonly SynchronizationContractLogMapper $synchronizationContractLogMapper
    ) {
        parent::__construct($appName, $request);

    }//end __construct()


    /**
     * Renders main page template
     *
     * @param string|null $getParameter Optional parameter from the request
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return TemplateResponse The template response
     */
    public function page(?string $getParameter): TemplateResponse
    {
        try {
            $response = new TemplateResponse(
                appName: $this->appName,
                templateName: 'index',
                params: []
            );

            $csp = new ContentSecurityPolicy();
            $csp->addAllowedConnectDomain(domain: '*');
            $response->setContentSecurityPolicy($csp);

            return $response;
        } catch (\Exception $e) {
            return new TemplateResponse(
                appName: $this->appName,
                templateName: 'error',
                params: ['error' => $e->getMessage()],
                renderAs: '500'
            );
        }

    }//end page()


    /**
     * Retrieves dashboard summary data
     *
     * Gets counts of various items for dashboard overview
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse Dashboard summary data
     */
    public function index(): JSONResponse
    {
        try {
            $results = [
                "sources"                  => $this->sourceMapper->getTotalCount(),
                "mappings"                 => $this->mappingMapper->getTotalCount(),
                "synchronizations"         => $this->synchronizationMapper->getTotalCount(),
                "synchronizationContracts" => $this->synchronizationContractMapper->getTotalCount(),
                "jobs"                     => $this->jobMapper->getTotalCount(),
                "endpoints"                => $this->endpointMapper->getTotalCount(),
            ];
            return new JSONResponse($results);
        } catch (\Exception $e) {
            return new JSONResponse(data: ['error' => $e->getMessage()], statusCode: 500);
        }

    }//end index()


    /**
     * Get call statistics for the dashboard
     *
     * @param string|null $from Start date in ISO format
     * @param string|null $to   End date in ISO format
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse Call statistics
     */
    public function getCallStats(?string $from=null, ?string $to=null): JSONResponse
    {
        try {
            // Set default from date to 7 days ago if not provided.
            $fromDate = null;
            if ($from === null) {
                $fromDate = (new \DateTime())->modify('-7 days');
            } else {
                $fromDate = new \DateTime($from);
            }

            // Set default to date to now if not provided.
            $toDate = null;
            if ($to === null) {
                $toDate = new \DateTime();
            } else {
                $toDate = new \DateTime($to);
            }

            $dailyStats  = $this->callLogMapper->getCallStatsByDateRange($fromDate, $toDate);
            $hourlyStats = $this->callLogMapper->getCallStatsByHourRange($fromDate, $toDate);

            return new JSONResponse(
                [
                    'daily'  => $dailyStats,
                    'hourly' => $hourlyStats,
                ]
            );
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

    }//end getCallStats()


    /**
     * Get job statistics for the dashboard
     *
     * @param string|null $from Start date in ISO format
     * @param string|null $to   End date in ISO format
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse Job statistics
     */
    public function getJobStats(?string $from=null, ?string $to=null): JSONResponse
    {
        try {
            // Set default from date to 7 days ago if not provided.
            $fromDate = null;
            if ($from === null) {
                $fromDate = (new \DateTime())->modify('-7 days');
            } else {
                $fromDate = new \DateTime($from);
            }

            // Set default to date to now if not provided.
            $toDate = null;
            if ($to === null) {
                $toDate = new \DateTime();
            } else {
                $toDate = new \DateTime($to);
            }

            $dailyStats  = $this->jobLogMapper->getJobStatsByDateRange($fromDate, $toDate);
            $hourlyStats = $this->jobLogMapper->getJobStatsByHourRange($fromDate, $toDate);

            return new JSONResponse(
                [
                    'daily'  => $dailyStats,
                    'hourly' => $hourlyStats,
                ]
            );
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

    }//end getJobStats()


    /**
     * Get synchronization statistics for the dashboard
     *
     * @param string|null $from Start date in ISO format
     * @param string|null $to   End date in ISO format
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse Synchronization statistics
     */
    public function getSyncStats(?string $from=null, ?string $to=null): JSONResponse
    {
        try {
            // Set default from date to 7 days ago if not provided.
            $fromDate = null;
            if ($from === null) {
                $fromDate = (new \DateTime())->modify('-7 days');
            } else {
                $fromDate = new \DateTime($from);
            }

            // Set default to date to now if not provided.
            $toDate = null;
            if ($to === null) {
                $toDate = new \DateTime();
            } else {
                $toDate = new \DateTime($to);
            }

            $dailyStats  = $this->synchronizationContractLogMapper->getSyncStatsByDateRange($fromDate, $toDate);
            $hourlyStats = $this->synchronizationContractLogMapper->getSyncStatsByHourRange($fromDate, $toDate);

            return new JSONResponse(
                [
                    'daily'  => $dailyStats,
                    'hourly' => $hourlyStats,
                ]
            );
        } catch (\Exception $e) {
            return new JSONResponse(['error' => $e->getMessage()], 500);
        }//end try

    }//end getSyncStats()


}//end class
