<?php
/**
 * OpenConnector Mapping Runtime Loader
 *
 * This file contains the runtime loader for Twig mapping runtime
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

use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Service\MappingService;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/**
 * Class MappingRuntimeLoader
 *
 * This class implements the RuntimeLoaderInterface for loading MappingRuntime instances.
 *
 * @package   OCA\OpenConnector\Twig
 * @category  Twig
 * @author    Conduction Development Team <dev@conductio.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 */
class MappingRuntimeLoader implements RuntimeLoaderInterface
{


    /**
     * Constructor
     *
     * @param MappingService $mappingService Service for mapping operations
     * @param MappingMapper  $mappingMapper  Mapper for mapping database operations
     *
     * @return void
     */
    public function __construct(
        private readonly MappingService $mappingService,
        private readonly MappingMapper $mappingMapper
    ) {

    }//end __construct()


    /**
     * Loads a runtime implementation based on the provided class name
     *
     * @param string $class The fully qualified class name
     *
     * @return MappingRuntime|null The runtime instance or null if not supported
     */
    public function load(string $class): ?MappingRuntime
    {
        if ($class === MappingRuntime::class) {
            return new MappingRuntime(mappingService: $this->mappingService, mappingMapper: $this->mappingMapper);
        }

        return null;

    }//end load()


}//end class
