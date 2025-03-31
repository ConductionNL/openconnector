<?php

declare(strict_types=1);

/**
 * SingleTest.php
 *
 * A simple test to demonstrate testing without Nextcloud dependencies.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Service
 * @author    Conduction <info@conduction.nl>
 * @copyright 2023 Conduction
 * @license   https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @version   GIT: <git_id>
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

/**
 * Class SingleTest
 *
 * A simple test that doesn't require Nextcloud dependencies.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Service
 * @author    Conduction <info@conduction.nl>
 * @license   EUPL-1.2
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */
class SingleTest extends TestCase
{
    /**
     * Test a simple assertion.
     *
     * @return void
     */
    public function testSimpleAssertion(): void
    {
        $this->assertTrue(true);
    }

    /**
     * Test a string operation.
     *
     * @return void
     */
    public function testStringOperation(): void
    {
        $result = $this->processString('Hello, world!');
        $this->assertEquals('HELLO, WORLD!', $result);
    }

    /**
     * Test array operations.
     *
     * @return void
     */
    public function testArrayOperations(): void
    {
        $input = [1, 2, 3, 4, 5];
        $result = $this->filterEvenNumbers($input);
        $this->assertEquals([2, 4], $result);
    }

    /**
     * Process a string by converting it to uppercase.
     *
     * @param string $input The input string
     *
     * @return string The processed string
     */
    private function processString(string $input): string
    {
        return strtoupper($input);
    }

    /**
     * Filter even numbers from an array.
     *
     * @param array<int> $input The input array
     *
     * @return array<int> The filtered array
     */
    private function filterEvenNumbers(array $input): array
    {
        return array_values(array_filter($input, function ($value) {
            return $value % 2 === 0;
        }));
    }
} 