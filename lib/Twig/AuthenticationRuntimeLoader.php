<?php
/**
 * OpenConnector Authentication Runtime Loader
 *
 * This file contains the runtime loader for Twig authentication runtime
 * in the OpenConnector application.
 *
 * @category  Twig
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: <git-id>
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Twig;

use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Service\AuthenticationService;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * Class AuthenticationRuntimeLoader
 *
 * This class implements the RuntimeLoaderInterface for loading AuthenticationRuntime instances.
 *
 * @package   OCA\OpenConnector\Twig
 * @category  Twig
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 */
class AuthenticationRuntimeLoader implements RuntimeLoaderInterface
{


    /**
     * Constructor
     *
     * @param AuthenticationService $authenticationService Service for authentication operations
     *
     * @return void
     */
    public function __construct(
        private readonly AuthenticationService $authenticationService,
    ) {

    }//end __construct()


    /**
     * Loads a runtime implementation based on the provided class name
     *
     * @param string $class The fully qualified class name
     *
     * @return AuthenticationRuntime|null The runtime instance or null if not supported
     */
    public function load(string $class): ?AuthenticationRuntime
    {
        if ($class === AuthenticationRuntime::class) {
            return new AuthenticationRuntime($this->authenticationService);
        }

        return null;

    }//end load()


}//end class
