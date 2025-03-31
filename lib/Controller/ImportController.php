<?php
/**
 * OpenConnector Import Controller
 *
 * This file contains the controller for handling import related operations
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
use OCA\OpenConnector\Service\ImportService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;

/**
 * Controller for handling import related operations
 *
 * @category  Controller
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 * @link      https://OpenConnector.app
 */
class ImportController extends Controller
{


    /**
     * Constructor for the ImportController
     *
     * @param string        $appName       The name of the app
     * @param IRequest      $request       The request object
     * @param IAppConfig    $config        The app configuration object
     * @param ImportService $importService The Import Service
     *
     * @return void
     */
    public function __construct(
        $appName,
        IRequest $request,
        private readonly IAppConfig $config,
        private readonly ImportService $importService
    ) {
        parent::__construct($appName, $request);

    }//end __construct()


    /**
     * Creates a new object or updates an existing one using a json text/string as input
     *
     * This method processes uploaded files and data for import operations.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the import results
     * @throws GuzzleException When an error occurs during the API call
     */
    public function import(): JSONResponse
    {
        $data          = $this->request->getParams();
        $uploadedFiles = [];

        // Check if multiple files have been uploaded.
        $files = ($_FILES['files'] ?? null);

        if (empty($files) === false && isset($files['name']) === true) {
            // Convert file data to indexed array of files.
            $fileNames    = $files['name'];
            $fileTypes    = $files['type'];
            $fileTmpNames = $files['tmp_name'];
            $fileErrors   = $files['error'];
            $fileSizes    = $files['size'];

            // Use array keys to iterate through all files.
            foreach (array_keys($fileNames) as $index) {
                $uploadedFiles[] = [
                    'name'     => $fileNames[$index],
                    'type'     => $fileTypes[$index],
                    'tmp_name' => $fileTmpNames[$index],
                    'error'    => $fileErrors[$index],
                    'size'     => $fileSizes[$index],
                ];
            }
        }

        // Get the uploaded file from the request if a single file has been uploaded.
        $uploadedFile = $this->request->getUploadedFile(key: 'file');
        if (empty($uploadedFile) === false) {
            $uploadedFiles[] = $uploadedFile;
        }

        return $this->importService->import(
                data: $data,
                uploadedFiles: $uploadedFiles
        );

    }//end import()


}//end class
