<?php
/**
 * OpenConnector Metrics Controller
 *
 * Controller for exposing Prometheus metrics in text exposition format.
 *
 * @category Controller
 * @package  OCA\OpenConnector\Controller
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.OpenConnector.nl
 */

declare(strict_types=1);

namespace OCA\OpenConnector\Controller;

use OCA\OpenConnector\Service\MetricsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TextPlainResponse;
use OCP\IRequest;

/**
 * Controller for exposing Prometheus metrics.
 *
 * Delegates metric collection to MetricsService and handles HTTP formatting.
 */
class MetricsController extends Controller
{


    /**
     * MetricsController constructor.
     *
     * @param string         $appName        The name of the app
     * @param IRequest       $request        Request object
     * @param MetricsService $metricsService The metrics collection service
     */
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly MetricsService $metricsService
    ) {
        parent::__construct($appName, $request);

    }//end __construct()


    /**
     * Expose Prometheus metrics.
     *
     * @return TextPlainResponse Plain text response with Prometheus metrics.
     *
     * @NoCSRFRequired
     */
    public function index(): TextPlainResponse
    {
        $metrics  = $this->metricsService->collect();
        $body     = $this->metricsService->format($metrics);
        $response = new TextPlainResponse($body);
        $response->addHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');

        return $response;

    }//end index()


}//end class
