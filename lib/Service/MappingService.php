<?php
/**
 * Mapping Service class.
 *
 * Provides functionality for mapping data between different structures,
 * including array transformations, value casting, and Twig templating.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service;

use OCA\OpenConnector\Db\Mapping;
use OCA\OpenConnector\Db\MappingMapper;
use OCA\OpenConnector\Twig\AuthenticationExtension;
use OCA\OpenConnector\Twig\AuthenticationRuntimeLoader;
use OCA\OpenConnector\Twig\MappingExtension;
use OCA\OpenConnector\Twig\MappingRuntimeLoader;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
// Use statements commented out below are not needed.
// Remove when confirmed they are unnecessary.
use Adbar\Dot;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Loader\ArrayLoader;

/**
 * Mapping Service class.
 */
class MappingService
{

    /**
     * Twig environment for templating.
     *
     * @var Environment
     */
    private Environment $twig;


    /**
     * Setting up the base class with required services.
     *
     * @param ArrayLoader   $loader        The ArrayLoader for Twig.
     * @param MappingMapper $mappingMapper The mapping mapper.
     *
     * @return void
     */
    public function __construct(
        ArrayLoader $loader,
        private readonly MappingMapper $mappingMapper
    ) {
        $this->twig = new Environment($loader);
        $this->twig->addExtension(new MappingExtension());
        $this->twig->addRuntimeLoader(new MappingRuntimeLoader(mappingService: $this, mappingMapper: $this->mappingMapper));

    }//end __construct()


    /**
     * Replaces strings in array keys, helpful for characters like . in array keys.
     *
     * @param array  $array       The array to encode the array keys for.
     * @param string $toReplace   The character to encode.
     * @param string $replacement The encoded character.
     *
     * @return array The array with encoded array keys.
     *
     * @psalm-param  array<string, mixed> $array
     * @psalm-return array<string, mixed>
     */
    public function encodeArrayKeys(array $array, string $toReplace, string $replacement): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = str_replace($toReplace, $replacement, $key);

            if (\is_array($value) === true && $value !== []) {
                $result[$newKey] = $this->encodeArrayKeys($value, $toReplace, $replacement);
                continue;
            }

            $result[$newKey] = $value;
        }

        return $result;

    }//end encodeArrayKeys()


    /**
     * Maps (transforms) an array (input) to a different array (output).
     *
     * @param Mapping $mapping The mapping object that forms the recipe for the mapping.
     * @param array   $input   The array that need to be mapped (transformed) otherwise known as input.
     * @param bool    $list    Whether we want a list instead of a single item.
     *
     * @return array The result (output) of the mapping process.
     *
     * @throws LoaderError When there is an error loading a Twig template.
     * @throws SyntaxError When there is a syntax error in a Twig template.
     *
     * @psalm-param  array<string, mixed> $input
     * @psalm-return array<string, mixed>
     */
    public function executeMapping(Mapping $mapping, array $input, bool $list=false): array
    {
        // Check for list.
        if ($list === true) {
            $list        = [];
            $extraValues = [];

            // Allow extra(input)values to be passed down for mapping while dealing with a list.
            if (array_key_exists('listInput', $input) === true) {
                $extraValues = $input;
                $input       = $input['listInput'];
                unset($extraValues['listInput'], $extraValues['value']);
            }

            foreach ($input as $key => $value) {
                // Mapping function expects an array for $input, make sure we always pass an array to this function.
                if (is_array($value) === false || empty($extraValues) === false) {
                    // We want to remove ['value' => $value] from this at some point, for now required for DOWR to work.
                    $value = array_merge((array) $value, ['value' => $value], $extraValues);
                }

                $list[$key] = $this->executeMapping($mapping, $value);
            }

            return $list;
        }//end if

        $originalInput = $input;
        $input         = $this->encodeArrayKeys($input, '.', '&#46;');

        // Determine pass through.
        // Let's get the dot array based on https://github.com/adbario/php-dot-notation.
        if ($mapping->getPassThrough() === true) {
            $dotArray = new Dot($input);
        } else {
            $dotArray = new Dot();
        }

        $dotInput = new Dot($input);

        // Let's do the actual mapping.
        foreach ($mapping->getMapping() as $key => $value) {
            // If the value exists in the input dot take it from there.
            if ($dotInput->has($value) === true) {
                $dotArray->set($key, $dotInput->get($value));
                continue;
            }

            // Render the value from twig.
            $dotArray->set($key, $this->twig->createTemplate($value)->render($originalInput));
        }

        // Unset unwanted key's.
        $unsets = ($mapping->getUnset() ?? []);
        foreach ($unsets as $unset) {
            if ($dotArray->has($unset) === false) {
                continue;
            }

            $dotArray->delete($unset);
        }

        // Cast values to a specific type.
        $casts = ($mapping->getCast() ?? []);

        foreach ($casts as $key => $cast) {
            if ($dotArray->has($key) === false) {
                continue;
            }

            if (is_array($cast) === false) {
                $cast = explode(',', $cast);
            }

            if ($cast === false) {
                continue;
            }

            foreach ($cast as $singleCast) {
                $this->handleCast($dotArray, $key, $singleCast);
            }
        }

        // Back to array.
        $output = $dotArray->all();

        $output = $this->encodeArrayKeys($output, '&#46;', '.');

        // If something has been defined to work on root level (i.e. the object lives on root level),
        // we can use # to define writing the root object.
        $keys = array_keys($output);
        if (count($keys) === 1 && $keys[0] === '#') {
            $output = $output['#'];
        }

        return $output;

    }//end executeMapping()


    /**
     * Handles a single cast.
     *
     * @param Dot    $dotArray The dotArray of the array we are mapping.
     * @param string $key      The key of the field we want to cast.
     * @param string $cast     The type of cast we want to do.
     *
     * @return void
     */
    private function handleCast(Dot $dotArray, string $key, string $cast): void
    {
        $value = $dotArray->get($key);

        // Variables for special cast operations.
        $unsetIfValue   = null;
        $setNullIfValue = null;
        $countValue     = null;

        // Parse special cast commands.
        if (str_starts_with($cast, 'unsetIfValue==') === true) {
            $unsetIfValue = substr($cast, 14);
            $cast         = 'unsetIfValue';
        } else if (str_starts_with($cast, 'setNullIfValue==') === true) {
            $setNullIfValue = substr($cast, 16);
            $cast           = 'setNullIfValue';
        } else if (str_starts_with($cast, 'countValue:') === true) {
            $countValue = substr($cast, 11);
            $cast       = 'countValue';
        }

        // Handle different cast types.
        switch ($cast) {
            case 'string':
                $value = (string) $value;
                break;
            case 'bool':
            case 'boolean':
                if ((int) $value === 1 || strtolower($value) === 'true' || strtolower($value) === 'yes') {
                    $value = true;
                } else {
                    $value = false;
                }
                break;
            case 'int':
            case 'integer':
                $value = (int) $value;
                break;
            case 'float':
                $value = (float) $value;
                break;
            case 'array':
                if (is_string($value) === true) {
                    $value = (json_decode($value, true) ?? []);
                }
                break;
            case 'split':
                // Split with comma as default delimiter.
                if (is_string($value) === true) {
                    $value = explode(",", $value);
                }
                break;
            case 'coordinates':
                if (is_string($value) === true) {
                    $value = $this->coordinateStringToArray($value);
                }
                break;
            case 'unsetIfNull':
                // Unset key if value is null.
                if ($value === null) {
                    $dotArray->delete($key);
                    return;
                }
                break;
            case 'unsetIfFalse':
                // Unset key if value is false.
                if ($value === false) {
                    $dotArray->delete($key);
                    return;
                }
                break;
            case 'unsetIfEmpty':
                // Unset key if value is empty (null, false, "", [], 0).
                if (empty($value) === true) {
                    $dotArray->delete($key);
                    return;
                }
                break;
            case 'unsetIfValue':
                // Unset key if value equals something specific.
                if ($value === $unsetIfValue) {
                    $dotArray->delete($key);
                    return;
                }
                break;
            case 'unsetIfArrayEmpty':
                // Unset key if value is empty array.
                if (is_array($value) === true && count($value) === 0) {
                    $dotArray->delete($key);
                    return;
                }
                break;
            case 'unsetIfArrayKeysNull':
                // Unset key if all array values are null.
                if (is_array($value) === true && $this->areAllArrayKeysNull($value) === true) {
                    $dotArray->delete($key);
                    return;
                }
                break;
            case 'setNullIfValue':
                // Set null if value equals something specific.
                if ($value === $setNullIfValue) {
                    $value = null;
                }
                break;
            case 'countValue':
                // Count amount of items in array.
                if (is_array($value) === true) {
                    $dotArray->set($countValue, count($value));
                } else {
                    $dotArray->set($countValue, 0);
                }
                break;
            case 'toYesNo':
                // Convert boolean to "yes" or "no".
                if ($value === true) {
                    $value = 'yes';
                } else {
                    $value = 'no';
                }
                break;
            default:
                // Unknown cast type, do nothing.
                break;
        }//end switch

        $dotArray->set($key, $value);

    }//end handleCast()


    /**
     * Check if all values in an array are null.
     *
     * @param array $array The array to check.
     *
     * @return bool True if all values are null, false otherwise.
     *
     * @psalm-param array<string, mixed> $array
     */
    private function areAllArrayKeysNull(array $array): bool
    {
        foreach ($array as $value) {
            if ($value !== null) {
                return false;
            }
        }

        return true;

    }//end areAllArrayKeysNull()


    /**
     * Parse a coordinate string into an array with lat and long.
     *
     * @param string $coordinates The coordinate string (can be in various formats).
     *
     * @return array The parsed coordinates as an array.
     *
     * @throws \Exception When coordinates are not in a valid format.
     *
     * @psalm-return array{lat: float, long: float}|array{}
     */
    public function coordinateStringToArray(string $coordinates): array
    {
        // Initialize empty result array.
        $result = [];

        // Check if the string is in JSON format.
        $jsonDecoded = json_decode($coordinates, true);
        if ($jsonDecoded !== null) {
            if (isset($jsonDecoded['lat'], $jsonDecoded['long']) === true) {
                return [
                    'lat'  => (float) $jsonDecoded['lat'],
                    'long' => (float) $jsonDecoded['long'],
                ];
            }

            if (isset($jsonDecoded['latitude'], $jsonDecoded['longitude']) === true) {
                return [
                    'lat'  => (float) $jsonDecoded['latitude'],
                    'long' => (float) $jsonDecoded['longitude'],
                ];
            }
        }

        // Try comma-separated format.
        $parts = explode(',', $coordinates);
        if (count($parts) === 2) {
            return [
                'lat'  => (float) trim($parts[0]),
                'long' => (float) trim($parts[1]),
            ];
        }

        return $result;

    }//end coordinateStringToArray()


    /**
     * Get a mapping by ID.
     *
     * @param string $mappingId The ID of the mapping to retrieve.
     *
     * @return Mapping The mapping object.
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException When the mapping doesn't exist.
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException When multiple mappings are found.
     * @throws \OCP\DB\Exception When there is a database error.
     */
    public function getMapping(string $mappingId): Mapping
    {
        return $this->mappingMapper->find($mappingId);

    }//end getMapping()


    /**
     * Get all mappings.
     *
     * @return Mapping[] Array of mappings.
     *
     * @throws \OCP\DB\Exception When there is a database error.
     */
    public function getMappings(): array
    {
        return $this->mappingMapper->findAll();

    }//end getMappings()


}//end class
