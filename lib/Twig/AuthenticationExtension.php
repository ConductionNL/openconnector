<?php
/**
 * OpenConnector Authentication Extension
 *
 * This file contains the Twig extension for handling authentication functions
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

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class AuthenticationExtension
 *
 * This class extends Twig's AbstractExtension to provide custom functions for Twig templates.
 *
 * @package   OCA\OpenConnector\Twig
 * @category  Twig
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 */
class AuthenticationExtension extends AbstractExtension
{


    /**
     * Get the list of custom Twig functions provided by this extension.
     *
     * @return array An array of TwigFunction objects
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(name: 'oauthToken', callable: [AuthenticationRuntime::class, 'oauthToken']),
            new TwigFunction(name: 'decosToken', callable: [AuthenticationRuntime::class, 'decosToken']),
            new TwigFunction(name: 'jwtToken', callable: [AuthenticationRuntime::class, 'jwtToken']),
        ];

    }//end getFunctions()


}//end class
