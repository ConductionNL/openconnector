<?php

namespace OCA\OpenConnector\Twig;

use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Service\MappingService;
use Twig\Extension\RuntimeExtensionInterface;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class MappingRuntimeLoader implements RuntimeLoaderInterface
{
	public function __construct(
		private readonly MappingService $mappingService,
		private readonly MappingMapper  $mappingMapper,
        private readonly CallService $callService,
        private readonly SourceMapper $sourceMapper,
	) {

	}

	public function load(string $class): ?MappingRuntime
	{
		if ($class === MappingRuntime::class) {
			return new MappingRuntime(mappingService: $this->mappingService, mappingMapper: $this->mappingMapper, callService: $this->callService, sourceMapper: $this->sourceMapper);
		}

		return null;
	}
}
