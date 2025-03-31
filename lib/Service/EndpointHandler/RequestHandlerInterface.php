<?php
/**
 * This file is part of the OpenConnector app.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service\EndpointHandler;

use OCA\OpenConnector\Db\Endpoint;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Interface for endpoint request handlers.
 *
 * This interface defines the contract for classes that handle different types of
 * endpoint requests, such as schema-based or API-based endpoints.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
interface RequestHandlerInterface
{
    /**
     * Determines if this handler can handle the given endpoint.
     *
     * @param Endpoint $endpoint The endpoint to check.
     *
     * @return bool True if this handler can handle the endpoint, false otherwise.
     */
    public function canHandle(Endpoint $endpoint): bool;

    /**
     * Handles the request to the given endpoint.
     *
     * @param Endpoint  $endpoint The endpoint configuration.
     * @param IRequest  $request  The incoming request.
     * @param string    $path     The path from the request.
     * @param array     $data     Additional data needed for handling the request.
     *
     * @return JSONResponse Response containing the result of the request.
     *
     * @psalm-param array<string, mixed> $data
     */
    public function handle(Endpoint $endpoint, IRequest $request, string $path, array $data): JSONResponse;
} 