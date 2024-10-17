<?php

namespace OCA\OpenConnector\Service;

use OCA\OpenConnector\Db\Mapping;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
//use Twig\Environment;
//use Twig\Error\LoaderError;
//use Twig\Error\SyntaxError;
use Adbar\Dot;

class MappingService
{
    /**
     * Create a private variable to store the twig environment.
     *
     * @var Environment
     */
    //private Environment $twig;

    /**
     * Setting up the base class with required services.
     *
     * @param Environment      $twig    The twig environment
     * @param SessionInterface $session The current session
     */
    public function __construct(
        //Environment $twig,
    ) {
        //$this->twig    = $twig;

    }//end __construct()

    /**
     * Replaces strings in array keys, helpful for characters like . in array keys.
     *
     * @param array  $array       The array to encode the array keys for.
     * @param string $toReplace   The character to encode.
     * @param string $replacement The encoded character.
     *
     * @return array The array with encoded array keys
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
     * @param Mapping $mappingObject The mapping object that forms the recipe for the mapping
     * @param array   $input         The array that need to be mapped (transformed) otherwise known as input
     * @param bool    $list          Wheter we want a list instead of a sngle item
     *
     * @throws LoaderError|SyntaxError Twig Exceptions
     *
     * @return array The result (output) of the mapping process
     */
    public function mapping(Mapping $mappingObject, array $input, bool $list = false): array
    {
        // Make sure we don't have BSONDocument (MongoDB) in our input.
        $input = $this->bsonDocumentToArray($input);

        // Check for list
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
                    // todo: we want to remove ['value' => $value] from this at some point, for now required for DOWR to work
                    $value = array_merge((array) $value, ['value' => $value], $extraValues);
                }

                $list[$key] = $this->mapping($mappingObject, $value);
            }

            return $list;
        }//end if

        $input = $this->encodeArrayKeys($input, '.', '&#46;');

        // @todo: error loging
        // isset($this->style) === true && $this->style->info('Mapping array based on mapping object '.$mappingObject->getName().' (id:'.$mappingObject->getId()->toString().' / ref:'.$mappingObject->getReference().') v:'.$mappingObject->getversion());

        // Determine pass trough.
        // Let's get the dot array based on https://github.com/adbario/php-dot-notation.
        if ($mappingObject->getPassThrough()) {
            $dotArray = new Dot($input);
            // @todo: error loging
            // isset($this->style) === true && $this->style->info('Mapping *with* pass trough');
        } else {
            $dotArray = new Dot();
            // @todo: error loging
            // isset($this->style) === true && $this->style->info('Mapping *without* pass trough');
        }

        $dotInput = new Dot($input);

        // Let's do the actual mapping.
        foreach ($mappingObject->getMapping() as $key => $value) {
            // If the value exists in the input dot take it from there.
            if ($dotInput->has($value)) {
                $dotArray->set($key, $dotInput->get($value));
                continue;
            }

            // Render the value from twig.
            // $dotArray->set($key, $this->twig->createTemplate($value)->render($input));
            $dotArray->set($key, $input);
        }

        // Unset unwanted key's.
        $unsets = ($mappingObject->getUnset() ?? []);
        foreach ($unsets as $unset) {
            if ($dotArray->has($unset) === false) {
                // @todo: error loging
                // isset($this->style) === true && $this->style->info("Trying to unset an property that doesn't exist during mapping");
                continue;
            }

            $dotArray->delete($unset);
        }

        // Cast values to a specific type.
        $casts = ($mappingObject->getCast() ?? []);

        foreach ($casts as $key => $cast) {
            if ($dotArray->has($key) === false) {
                // @todo: error loging
                //isset($this->style) === true && $this->style->info("Trying to cast an property that doesn't exist during mapping");
                continue;
            }

            if (is_array($cast) === false) {
                $cast = explode(',', $cast);
            }

            if ($cast === false) {
                // @todo: error loging
                //isset($this->style) === true && $this->style->info("Cast for property $key is an empty string");
                continue;
            }

            foreach ($cast as $singleCast) {
                $this->handleCast($dotArray, $key, $singleCast);
            }
        }

        // Back to array.
        $output = $dotArray->all();

        $output = $this->encodeArrayKeys($output, '&#46;', '.');

        // If something has been defined to work on root level (i.e. the object lives on root level), we can use # to define writing the root object.
        $keys = array_keys($output);
        if (count($keys) === 1 && $keys[0] === '#') {
            $output = $output['#'];
        }

        // Log the result.
        // @todo: error handling
        /*
        isset($this->style) === true && $this->style->info(
            'Mapped object',
            [
                'input'      => $input,
                'output'     => $output,
                'passThrough' => $mappingObject->getPassThrough(),
                'mapping'    => $mappingObject->getMapping(),
            ]
        );
        */

        return $output;

    }//end mapping()

    /**
     * Handles a single cast.
     *
     * @param Dot    $dotArray The dotArray of the array we are mapping.
     * @param string $key      The key of the field we want to cast.
     * @param string $cast     The type of cast we want to do.
     *
     * @return void
     */
    private function handleCast(Dot $dotArray, string $key, string $cast)
    {
        $value = $dotArray->get($key);

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

        // Todo: Add more casts.
        switch ($cast) {
        case 'string':
            $value = (string) $value;
            break;
        case 'bool':
        case 'boolean':
            if ((int) $value === 1 || strtolower($value) === 'true' || strtolower($value) === 'yes') {
                $value = true;
                break;
            }

            $value = false;
            break;
        case 'int':
        case 'integer':
            $value = (int) $value;
            break;
        case 'float':
            $value = (float) $value;
            break;
        case 'array':
            $value = (array) $value;
            break;
        case 'date':
            $value = date($value);
            break;
        case 'url':
            $value = urlencode($value);
            break;
        case 'urlDecode':
            $value = urldecode($value);
            break;
        case 'rawurl':
            $value = rawurlencode($value);
            break;
        case 'rawurlDecode':
            $value = rawurldecode($value);
            break;
        case 'html':
            $value = htmlentities($value);
            break;
        case 'htmlDecode':
            $value = html_entity_decode($value);
            break;
        case 'base64':
            $value = base64_encode($value);
            break;
        case 'base64Decode':
            $value = \Safe\base64_decode($value);
            break;
        case 'json':
            $value = json_encode($value);
            break;
        case 'jsonToArray':
            $value = html_entity_decode($value);
            $value = json_decode($value, true);
            break;
        case 'utf8':
            // https://www.php.net/manual/en/function.iconv.php
            setlocale(LC_CTYPE, 'cs_CZ');
            $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
            break;
        case 'nullStringToNull':
            if ($value === 'null') {
                $value = null;
            }
            break;
        case 'coordinateStringToArray':
            $value = $this->coordinateStringToArray($value);
            break;
        case 'keyCantBeValue':
            if ($key == $value) {
                $dotArray->delete($key);
            }
            break;
        case 'unsetIfValue':
            if (isset($unsetIfValue) === true
                && $value == $unsetIfValue
                || ($unsetIfValue === '' && empty($value))
                || ($unsetIfValue === '' && $value === null)
            ) {
                $dotArray->delete($key);
            }

            if ($unsetIfValue === '' && is_array($value) === true && $this->areAllArrayKeysNull($value) === true) {
                $dotArray->delete($key);
            }
            break;
        case 'setNullIfValue':
            if (isset($setNullIfValue) === true
                && $value == $setNullIfValue
                || ($setNullIfValue === '' && empty($value))
                || ($setNullIfValue === '' && $value === null)
            ) {
                $value = null;
            }

            if ($setNullIfValue === '' && is_array($value) === true && $this->areAllArrayKeysNull($value) === true) {
                $value = null;
            }
            break;
        case 'countValue':
            if (isset($countValue) === true
                && empty($countValue) === false
                && $dotArray->has($countValue) === true
                && is_countable($dotArray->get($countValue)) === true
            ) {
                $value = count($dotArray->get($countValue));
            }
            break;
        case 'moneyStringToInt':
            $value = str_replace('.', '', $value);
            $value = (int) str_replace(',', '', $value);
            break;
        case 'intToMoneyString':
            $value = ($value / 100);
            $value = number_format($value, 2, ',', '.');
            break;
        default:
            // @todo: error handling
            //isset($this->style) === true && $this->style->info('Trying to cast to an unsupported cast type: '.$cast);
            break;
        }//end switch

        // Don't reset key that was deleted on purpose.
        if ($dotArray->has($key)) {
            $dotArray->set($key, $value);
        }

    }//end handleCast()

    /**
     * Checks if all keys in multi-dimensional array are null.
     *
     * @param array $array Array to check.
     *
     * @return bool True if array keys are null else false.
     */
    private function areAllArrayKeysNull(array $array): bool
    {
        if (empty($array) === true) {
            return true;
        }

        foreach ($array as $value) {
            if (is_array($value) === true) {
                if ($this->areAllArrayKeysNull($value) === false) {
                    return false;
                }
            } else if (empty($value) === false) {
                return false;
            }
        }

        return true;

    }//end areAllArrayKeysNull()

    /**
     * Converts a coordinate string to an array of coordinates.
     *
     * @param string $coordinates A string containing coordinates.
     *
     * @return array An array of coordinates.
     */
    public function coordinateStringToArray(string $coordinates): array
    {
        $halfs           = explode(' ', $coordinates);
        $point           = [];
        $coordinateArray = [];
        foreach ($halfs as $half) {
            if (count($point) > 1) {
                $coordinateArray[] = $point;
                $point             = [];
            }

            $point[] = $half;
        }//end foreach

        $coordinateArray[] = $point;

        if (count($coordinateArray) === 1) {
            $coordinateArray = $coordinateArray[0];
        }

        return $coordinateArray;

    }//end coordinateStringToArray()

}
