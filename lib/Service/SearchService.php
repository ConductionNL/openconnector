<?php
/**
 * Service class for handling search operations.
 *
 * This service provides functionality for searching across different data sources,
 * including elastic search and databases. It also provides utilities for
 * processing and formatting search results.
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

use GuzzleHttp\Client;
use GuzzleHttp\Promise\Utils;
use OCP\IURLGenerator;
use Symfony\Component\Uid\Uuid;

/**
 * Service class for handling search operations.
 */
class SearchService
{

    /**
     * The HTTP client for making requests.
     *
     * @var Client
     */
    public $client;

    /**
     * Base object configuration for searching.
     *
     * @var array<string, string>
     */
    public const BASE_OBJECT = [
        'database'   => 'objects',
        'collection' => 'json',
    ];


    /**
     * Constructor.
     *
     * @param IURLGenerator $urlGenerator URL generator for creating endpoint URLs.
     *
     * @return void
     */
    public function __construct(
        private readonly IURLGenerator $urlGenerator,
    ) {
        $this->client = new Client();

    }//end __construct()


    /**
     * Merges facet arrays from different result sets.
     *
     * @param array $existingAggregation The existing aggregation data.
     * @param array $newAggregation      The new aggregation data to merge.
     *
     * @return array The merged facet arrays.
     *
     * @psalm-param  array<int, array<string, mixed>> $existingAggregation
     * @psalm-param  array<int, array<string, mixed>> $newAggregation
     * @psalm-return array<int, array<string, mixed>>
     */
    public function mergeFacets(array $existingAggregation, array $newAggregation): array
    {
        $results = [];
        $existingAggregationMapped = [];
        $newAggregationMapped      = [];

        foreach ($existingAggregation as $value) {
            $existingAggregationMapped[$value['_id']] = $value['count'];
        }

        foreach ($newAggregation as $value) {
            if (isset($existingAggregationMapped[$value['_id']]) === true) {
                $newAggregationMapped[$value['_id']] = ($existingAggregationMapped[$value['_id']] + $value['count']);
            } else {
                $newAggregationMapped[$value['_id']] = $value['count'];
            }
        }

        // This line exceeds the maximum limit of 150 characters, split it into multiple lines.
        $mergedArrays = array_merge(
            array_diff($existingAggregationMapped, $newAggregationMapped),
            array_diff($newAggregationMapped, $existingAggregationMapped)
        );

        foreach ($mergedArrays as $key => $value) {
            $results[] = [
                '_id'   => $key,
                'count' => $value,
            ];
        }

        return $results;

    }//end mergeFacets()


    /**
     * Merges aggregation arrays from different result sets.
     *
     * @param array|null $existingAggregations The existing aggregations.
     * @param array|null $newAggregations      The new aggregations to merge.
     *
     * @return array The merged aggregation arrays.
     *
     * @psalm-param  array<string, mixed>|null $existingAggregations
     * @psalm-param  array<string, mixed>|null $newAggregations
     * @psalm-return array<string, mixed>
     */
    private function mergeAggregations(?array $existingAggregations, ?array $newAggregations): array
    {
        if ($newAggregations === null) {
            return [];
        }

        foreach ($newAggregations as $key => $aggregation) {
            if (isset($existingAggregations[$key]) === false) {
                $existingAggregations[$key] = $aggregation;
            } else {
                $existingAggregations[$key] = $this->mergeFacets($existingAggregations[$key], $aggregation);
            }
        }

        return $existingAggregations;

    }//end mergeAggregations()


    /**
     * Sort comparison function for result arrays.
     *
     * @param array $a First array to compare.
     * @param array $b Second array to compare.
     *
     * @return int Comparison result (-1, 0, 1).
     *
     * @psalm-param array<string, mixed> $a
     * @psalm-param array<string, mixed> $b
     */
    public function sortResultArray(array $a, array $b): int
    {
        return ($a['_score'] <=> $b['_score']);

    }//end sortResultArray()


    /**
     * Perform a search across multiple data sources.
     *
     * This method handles searching across elastic and other data sources,
     * combining and formatting the results.
     *
     * @param array $parameters    Search parameters and filters.
     * @param array $elasticConfig Configuration for elastic search.
     * @param array $dbConfig      Configuration for database search.
     * @param array $catalogi      Optional catalog IDs to filter on.
     *
     * @return array Search results with facets, pagination, and count information.
     *
     * @psalm-param  array<string, mixed> $parameters
     * @psalm-param  array<string, mixed> $elasticConfig
     * @psalm-param  array<string, mixed> $dbConfig
     * @psalm-param  array<int, string> $catalogi
     * @psalm-return array<string, mixed>
     */
    public function search(array $parameters, array $elasticConfig, array $dbConfig, array $catalogi=[]): array
    {
        $localResults['results'] = [];
        $localResults['facets']  = [];

        $totalResults = 0;
        // Replace inline IF statements with proper if/else assignments.
        $limit = 30;
        if (isset($parameters['.limit']) === true) {
            $limit = $parameters['.limit'];
        }

        $page = 1;
        if (isset($parameters['.page']) === true) {
            $page = $parameters['.page'];
        }

        if ($elasticConfig['location'] !== '') {
            $localResults = $this->elasticService->searchObject(
                filters: $parameters,
                config: $elasticConfig,
                totalResults: $totalResults
            );
        }

        $directory = $this->directoryService->listDirectory(limit: 1000);

        // $directory = $this->objectService->findObjects(filters: ['_schema' => 'directory'], config: $dbConfig);
        if (count($directory) === 0) {
            $pages = (int) ceil($totalResults / $limit);

            // Replace inline IF with proper if/else.
            $finalPages = $pages;
            if ($pages === 0) {
                $finalPages = 1;
            }

            return [
                'results' => $localResults['results'],
                'facets'  => $localResults['facets'],
                'count'   => count($localResults['results']),
                'limit'   => $limit,
                'page'    => $page,
                'pages'   => $finalPages,
                'total'   => $totalResults,
            ];
        }

        $results      = $localResults['results'];
        $aggregations = $localResults['facets'];

        $searchEndpoints = [];

        $promises = [];
        foreach ($directory as $instance) {
            $shouldSkip = false;

            if ($instance['default'] === false) {
                $shouldSkip = true;
            }

            if (isset($parameters['.catalogi']) === true
                && in_array($instance['catalogId'], $parameters['.catalogi']) === false
            ) {
                $shouldSkip = true;
            }

            $instanceUrl = $this->urlGenerator->getAbsoluteURL(
                $this->urlGenerator->linkToRoute(routeName: "opencatalogi.directory.index")
            );

            if ($instance['search'] === $instanceUrl) {
                $shouldSkip = true;
            }

            if ($shouldSkip === true) {
                continue;
            }

            $searchEndpoints[$instance['search']][] = $instance['catalogId'];
        }//end foreach

        unset($parameters['.catalogi']);

        foreach ($searchEndpoints as $searchEndpoint => $catalogi) {
            $parameters['_catalogi'] = $catalogi;

            $promises[] = $this->client->getAsync($searchEndpoint, ['query' => $parameters]);
        }

        $responses = Utils::settle($promises)->wait();

        foreach ($responses as $response) {
            if ($response['state'] === 'fulfilled') {
                $responseData = json_decode(
                    json: $response['value']->getBody()->getContents(),
                    associative: true
                );

                $results = array_merge(
                    $results,
                    $responseData['results']
                );

                usort($results, [$this, 'sortResultArray']);

                $aggregations = $this->mergeAggregations($aggregations, $responseData['facets']);
            }
        }

        $pages = (int) ceil($totalResults / $limit);

        // Replace inline IF with proper if/else.
        $finalPages = $pages;
        if ($pages === 0) {
            $finalPages = 1;
        }

        return [
            'results' => $results,
            'facets'  => $aggregations,
            'count'   => count($results),
            'limit'   => $limit,
            'page'    => $page,
            'pages'   => $finalPages,
            'total'   => $totalResults,
        ];

    }//end search()


    /**
     * This function adds a single query param to the given $vars array. ?$name=$value
     * Will check if request query $name has [...] inside the parameter, like this: ?queryParam[$nameKey]=$value.
     * Works recursive, so in case we have ?queryParam[$nameKey][$anotherNameKey][etc][etc]=$value.
     * Also checks for queryParams ending on [] like: ?queryParam[$nameKey][] (or just ?queryParam[]), if this is the case
     * this function will add given value to an array of [queryParam][$nameKey][] = $value or [queryParam][] = $value.
     * If none of the above this function will just add [queryParam] = $value to $vars.
     *
     * @param array  $vars    The vars array we are going to store the query parameter in.
     * @param string $name    The full $name of the query param, like this: ?$name=$value.
     * @param string $nameKey The full $name of the query param, unless it contains [] like: ?queryParam[$nameKey]=$value.
     * @param string $value   The full $value of the query param, like this: ?$name=$value.
     *
     * @return void
     *
     * @psalm-param array<string, mixed> $vars
     */
    private function recursiveRequestQueryKey(array &$vars, string $name, string $nameKey, string $value): void
    {
        $matchesCount = preg_match(pattern: '/(\[[^[\]]*])/', subject: $name, matches: $matches);
        if ($matchesCount > 0) {
            $key  = $matches[0];
            $name = str_replace(search: $key, replace: '', subject: $name);
            $key  = trim(string: $key, characters: '[]');
            if (empty($key) === false) {
                $vars[$nameKey] = ($vars[$nameKey] ?? []);
                $this->recursiveRequestQueryKey(
                    vars: $vars[$nameKey],
                    name: $name,
                    nameKey: $key,
                    value: $value
                );
            } else {
                $vars[$nameKey][] = $value;
            }
        } else {
            $vars[$nameKey] = $value;
        }

    }//end recursiveRequestQueryKey()


    /**
     * This function creates a mongodb filter array.
     *
     * Also unsets _search in filters !
     *
     * @param array $filters        Query parameters from request.
     * @param array $fieldsToSearch Database field names to filter/search on.
     *
     * @return array The MongoDB filter array.
     *
     * @psalm-param  array<string, mixed> $filters
     * @psalm-param  array<int, string> $fieldsToSearch
     * @psalm-return array<string, mixed>
     */
    public function createMongoDBSearchFilter(array $filters, array $fieldsToSearch): array
    {
        if (isset($filters['_search']) === true) {
            $searchRegex    = [
                '$regex'   => $filters['_search'],
                '$options' => 'i',
            ];
            $filters['$or'] = [];

            foreach ($fieldsToSearch as $field) {
                $filters['$or'][] = [$field => $searchRegex];
            }

            unset($filters['_search']);
        }

        foreach ($filters as $field => $value) {
            if ($value === 'IS NOT NULL') {
                $filters[$field] = ['$ne' => null];
            }

            if ($value === 'IS NULL') {
                $filters[$field] = ['$eq' => null];
            }
        }

        return $filters;

    }//end createMongoDBSearchFilter()


    /**
     * This function creates mysql search conditions based on given filters from request.
     *
     * @param array $filters        Query parameters from request.
     * @param array $fieldsToSearch Fields to search on in sql.
     *
     * @return array The search conditions for MySQL.
     *
     * @psalm-param  array<string, mixed> $filters
     * @psalm-param  array<int, string> $fieldsToSearch
     * @psalm-return array<int, string>
     */
    public function createMySQLSearchConditions(array $filters, array $fieldsToSearch): array
    {
        $searchConditions = [];
        if (isset($filters['_search']) === true) {
            foreach ($fieldsToSearch as $field) {
                $searchConditions[] = "LOWER($field) LIKE :search";
            }
        }

        return $searchConditions;

    }//end createMySQLSearchConditions()


    /**
     * This function unsets all keys starting with _ from filters.
     *
     * @param array $filters Query parameters from request.
     *
     * @return array The filtered parameters.
     *
     * @psalm-param  array<string, mixed> $filters
     * @psalm-return array<string, mixed>
     */
    public function unsetSpecialQueryParams(array $filters): array
    {
        foreach ($filters as $key => $value) {
            if (str_starts_with($key, '_') === true) {
                unset($filters[$key]);
            }
        }

        return $filters;

    }//end unsetSpecialQueryParams()


    /**
     * This function creates mysql search parameters based on given filters from request.
     *
     * @param array $filters Query parameters from request.
     *
     * @return array The search parameters for MySQL.
     *
     * @psalm-param  array<string, mixed> $filters
     * @psalm-return array<string, string>
     */
    public function createMySQLSearchParams(array $filters): array
    {
        $searchParams = [];
        if (isset($filters['_search']) === true) {
            $searchParams['search'] = '%'.strtolower($filters['_search']).'%';
        }

        return $searchParams;

    }//end createMySQLSearchParams()


    /**
     * This function creates an sort array based on given order param from request.
     *
     * @param array $filters Query parameters from request.
     *
     * @return array The sort array for MySQL.
     *
     * @psalm-param  array<string, mixed> $filters
     * @psalm-return array<string, string>
     */
    public function createSortForMySQL(array $filters): array
    {
        $sort = [];
        if (isset($filters['_order']) === true && is_array($filters['_order']) === true) {
            foreach ($filters['_order'] as $field => $direction) {
                $direction = 'ASC';
                if (strtoupper($direction) === 'DESC') {
                    $direction = 'DESC';
                }

                $sort[$field] = $direction;
            }
        }

        return $sort;

    }//end createSortForMySQL()


    /**
     * This function creates an sort array based on given order param from request.
     *
     * @param array $filters Query parameters from request.
     *
     * @return array The sort array for MongoDB.
     *
     * @psalm-param  array<string, mixed> $filters
     * @psalm-return array<string, int>
     *
     * @todo Not functional yet. Needs to be fixed (see PublicationsController->index).
     */
    public function createSortForMongoDB(array $filters): array
    {
        $sort = [];
        if (isset($filters['_order']) === true && is_array($filters['_order']) === true) {
            foreach ($filters['_order'] as $field => $direction) {
                $sort[$field] = 1;
                if (strtoupper($direction) === 'DESC') {
                    $sort[$field] = -1;
                }
            }
        }

        return $sort;

    }//end createSortForMongoDB()


    /**
     * Parses the request query string and returns it as an array of queries.
     *
     * @param string $queryString The input query string from the request.
     *
     * @return array The resulting array of query parameters.
     *
     * @psalm-param  string $queryString
     * @psalm-return array<string, mixed>
     */
    public function parseQueryString(string $queryString=''): array
    {
        $pairs = explode(separator: '&', string: $queryString);
        $vars  = [];

        foreach ($pairs as $pair) {
            $kvpair = explode(separator: '=', string: $pair);

            $key   = urldecode(string: $kvpair[0]);
            $value = '';
            if (count(value: $kvpair) === 2) {
                $value = urldecode(string: $kvpair[1]);
            }

            // Get the position of the first '[' for the nameKey.
            $bracketPos = strpos(haystack: $key, needle: '[');
            $nameKey    = $key;

            if ($bracketPos !== false) {
                $nameKey = substr(string: $key, offset: 0, length: $bracketPos);
            }

            $this->recursiveRequestQueryKey(
                vars: $vars,
                name: $key,
                nameKey: $nameKey,
                value: $value
            );
        }//end foreach

        return $vars;

    }//end parseQueryString()


}//end class
