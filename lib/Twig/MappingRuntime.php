<?php

namespace OCA\OpenConnector\Twig;

use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Service\AuthenticationService;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Service\MappingService;
use Twig\Extension\RuntimeExtensionInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV4;

class MappingRuntime implements RuntimeExtensionInterface
{
	public function __construct(
		private readonly MappingService $mappingService,
		private readonly MappingMapper  $mappingMapper,
        private readonly CallService $callService,
        private readonly SourceMapper $sourceMapper,
	) {

	}

    /**
     * Encodes a string to base64.
     *
     * @param string $input The unencoded input.
     * @return string The encoded output.
     */
    public function b64enc(string $input): string
    {
        return base64_encode($input);

    }//end b64enc()

    /**
     * Decodes a base64 encoded string to an unencoded string.
     *
     * @param string $input The encoded input.
     * @return string The decoded output.
     */
    public function b64dec(string $input): string
    {
        return base64_decode($input);

    }//end b64dec()

    public function json_decode(string $input): array
    {
        return json_decode($input, associative: true);
    }

    /**
     * Call source of given id or reference
     *
     * @param array $array The array to turn into a dot array.
     *
     * @return array The dot aray.
     */
    public function callSource(string $sourceId, string $endpoint, string $method='GET', array $configuration=[], bool $decode=true): array|string
    {
        $source = $this->sourceMapper->find($sourceId);

        if (str_contains($endpoint, $source->getLocation()) === true) {
            $endpoint = substr($endpoint, strlen($source->getLocation()));
        }

        $response = $this->callService->call($source, $endpoint, $method, $configuration);

        if ($decode === false) {
            return $response->getResponse()['body'];
        }

        return $response->getResponse()['body'];

    }//end call()

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

	/**
	 * Generate a uuid.
	 *
	 * @return array
	 */
	public function generateUuid(): UuidV4
	{
		return Uuid::v4();
	}
}
