<?php
/**
 * OpenConnector - Connect your Nextcloud to external services
 *
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

use OCA\OpenConnector\Service\ExportService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;

/**
 * Controller for handling object exports in various formats *
 */
class ExportController extends Controller
{
    /**
     * Constructor for the ExportController
     *
     * @param string        $appName       The name of the app
     * @param IRequest      $request       The request object
     * @param IAppConfig    $config        The app configuration object
     * @param ExportService $exportService The Export Service
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
        private IAppConfig $config,
        private readonly ExportService $exportService
    ) {
        parent::__construct($appName, $request);
    }//end __construct()

    /**
     * Creates and return a json file for a specific object
     *
     * @param string $type The object type we want to export an object for
     * @param string $id   The id used to find an existing object to export
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse
     */
    public function export(string $type, string $id): JSONResponse
    {
        $accept = $this->request->getHeader(name: 'Accept');

        if (empty($accept) === true) {
            return new JSONResponse(data: ['error' => 'Request is missing header Accept'], statusCode: 400);
        }

        return $this->exportService->export(objectType: $type, id: $id, accept: $accept);
    }//end export()
}//end class
