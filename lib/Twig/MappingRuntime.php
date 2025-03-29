<?php

namespace OCA\OpenConnector\Twig;

use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Service\AuthenticationService;
use OCA\OpenConnector\Service\MappingService;
use Twig\Extension\RuntimeExtensionInterface;

class MappingRuntime implements RuntimeExtensionInterface
{
	public function __construct(
		private readonly MappingService $mappingService,
		private readonly MappingMapper  $mappingMapper
	) {

	}

	/**
	 * Execute a mapping with given parameters.
	 *
	 * @param Mapping|array|string|int $mapping The mapping to execute
	 * @param array $input The input to run the mapping on
	 * @param bool $list Whether the mapping runs on multiple instances of the object.
	 *
	 * @return array
	 */
	public function executeMapping(Mapping|array|string|int $mapping, array $input, bool $list = false): array
	{
		if (is_array($mapping) === true) {
			$mappingObject = new Mapping();
			$mappingObject->hydrate($mapping);

			$mapping = $mappingObject;
		} else if (is_string($mapping) === true || is_int($mapping) === true) {
			if (is_string($mapping) === true && str_starts_with($mapping, 'http')) {
				$mapping = $this->mappingMapper->findByRef($mapping)[0];
			} else {
				// If the mapping is an int, we assume it's an ID and try to find the mapping by ID.
				// In the future we should be able to find the mapping by uuid (string) as well.
				$mapping = $this->mappingMapper->find($mapping);
			}
		}

		return $this->mappingService->executeMapping(
			mapping: $mapping, input: $input, list: $list
		);
	}


}
