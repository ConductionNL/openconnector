<?php
/**
 * OpenConnector Mapping Runtime
 *
 * This file contains the runtime class for Twig mapping functions
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

use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Service\AuthenticationService;
use OCA\OpenConnector\Service\MappingService;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Class MappingRuntime
 *
 * This class implements the RuntimeExtensionInterface for providing mapping functions to Twig templates.
 */
class MappingRuntime implements RuntimeExtensionInterface
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
     * Execute a mapping with given parameters
     *
     * @param Mapping|array|string|int $inputMapping The mapping to execute
     * @param array                    $input        The input to run the mapping on
     * @param bool                     $list         Whether the mapping runs on multiple instances of the object
     *
     * @return array The mapped result
     */
    public function executeMapping((Mapping | array | string | int $inputMapping), array $input, bool $list=false): array
    {
        if (is_array($inputMapping) === true) {
            $mappingObject = new Mapping();
            $mappingObject->hydrate($inputMapping);

            $inputMapping = $mappingObject;
        } else if ((is_string($inputMapping) === true) || (is_int($inputMapping) === true)) {
            if ((is_string($inputMapping) === true) && str_starts_with($inputMapping, 'http') === true) {
                $inputMapping = $this->mappingMapper->findByRef($inputMapping)[0];
            } else {
                // If the inputMapping is an int, we assume it's an ID and try to find the mapping by ID.
                // In the future we should be able to find the mapping by uuid (string) as well.
                $inputMapping = $this->mappingMapper->find($inputMapping);
            }
        }

        return $this->mappingService->executeMapping(
            mapping: $inputMapping,
            input: $input,
            list: $list
        );

    }//end executeMapping()


}//end class
