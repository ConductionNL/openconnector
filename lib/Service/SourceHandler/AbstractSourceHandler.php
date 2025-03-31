<?php

/**
 * Abstract base class for source handlers.
 *
 * @category   Service
 * @package    OpenConnector
 * @subpackage SourceHandler
 * @author     Conduction B.V.
 * @copyright  Copyright (C) 2024 Conduction B.V. All rights reserved.
 * @license    EUPL 1.2
 * @version    GIT: <git_id>
 * @link       https://openregister.app
 *
 * @since 1.0.0 - Description of when this class was added
 */

namespace OCA\OpenConnector\Service\SourceHandler;

use OCA\OpenConnector\Service\CallService;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Adbar\Dot;

/**
 * Abstract base class for source handlers.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction B.V.
 * @copyright Copyright (C) 2024 Conduction B.V. All rights reserved.
 * @license   EUPL 1.2
 * @version   GIT: <git_id>
 * @link      https://openregister.app
 *
 * @since 1.0.0 - Description of when this class was added
 */
abstract class AbstractSourceHandler implements SourceHandlerInterface
{

    /**
     * @var CallService The service for making HTTP calls
     */
    protected CallService $callService;


    /**
     * Constructor.
     *
     * @param CallService $callService The service for making HTTP calls
     */
    public function __construct(CallService $callService)
    {
        $this->callService = $callService;

    }//end __construct()


    /**
     * Checks rate limits and throws an exception if exceeded.
     *
     * @param Source $source The source to check rate limits for
     *
     * @return void
     *
     * @throws TooManyRequestsHttpException If rate limit is exceeded
     */
    protected function checkRateLimit(Source $source): void
    {
        if ($source->getRateLimitRemaining() !== null
            && $source->getRateLimitReset() !== null
            && $source->getRateLimitRemaining() <= 0
            && $source->getRateLimitReset() > time()
        ) {
            throw new TooManyRequestsHttpException(
                message: "Rate Limit on Source has been exceeded. Canceling synchronization...",
                code: 429,
                headers: $this->getRateLimitHeaders($source)
            );
        }

    }//end checkRateLimit()


    /**
     * Gets rate limit headers from a source.
     *
     * @param Source $source The source to get headers from
     *
     * @return array The rate limit headers
     */
    protected function getRateLimitHeaders(Source $source): array
    {
        return [
            'X-RateLimit-Limit'     => $source->getRateLimitLimit(),
            'X-RateLimit-Remaining' => $source->getRateLimitRemaining(),
            'X-RateLimit-Reset'     => $source->getRateLimitReset(),
            'X-RateLimit-Used'      => 0,
            'X-RateLimit-Window'    => $source->getRateLimitWindow(),
        ];

    }//end getRateLimitHeaders()


    /**
     * Extracts objects from a response array based on configuration.
     *
     * @param array $array  The response array
     * @param array $config The source configuration
     *
     * @return array The extracted objects
     *
     * @throws \Exception If objects cannot be found in the response
     */
    protected function extractObjects(array $array, array $config): array
    {
        // Check for specific results position in config.
        if (empty($config['resultsPosition']) === false) {
            $position = $config['resultsPosition'];
            if ($position === '_root' || $position === '_object') {
                return $array;
            }

            $dot = new Dot($array);
            if ($dot->has($position) === true) {
                return $dot->get($position);
            }

            return [];
        }

        // Check common keys.
        $commonKeys = [
            'items',
            'result',
            'results',
        ];
        foreach ($commonKeys as $key) {
            if (isset($array[$key]) === true) {
                return $array[$key];
            }
        }

        throw new \Exception("Cannot determine the position of objects in the response body.");

    }//end extractObjects()


}//end class
