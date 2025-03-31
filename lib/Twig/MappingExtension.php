<?php
/**
 * OpenConnector Mapping Extension
 *
 * This file contains the Twig extension for handling mapping functions
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
 * Class MappingExtension
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
class MappingExtension extends AbstractExtension
{


    /**
     * Get the list of custom Twig functions provided by this extension.
     *
     * @return array An array of TwigFunction objects
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction(name: 'executeMapping', callable: [MappingRuntime::class, 'executeMapping']),
        ];

    }//end getFunctions()


}//end class
