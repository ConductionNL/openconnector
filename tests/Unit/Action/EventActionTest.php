<?php

declare(strict_types=1);

/**
 * EventActionTest
 *
 * Comprehensive unit tests for the EventAction class to verify event action
 * functionality and execution.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Action
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Action;

use OCA\OpenConnector\Action\EventAction;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Db\SourceMapper;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Event Action Test Suite
 *
 * Comprehensive unit tests for event action functionality including
 * execution and return value handling.
 *
 * @coversDefaultClass EventAction
 */
class EventActionTest extends TestCase
{
    private EventAction $eventAction;
    private CallService|MockObject $callService;
    private SourceMapper|MockObject $sourceMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->callService = $this->createMock(CallService::class);
        $this->sourceMapper = $this->createMock(SourceMapper::class);
        
        $this->eventAction = new EventAction(
            $this->callService,
            $this->sourceMapper
        );
    }

    /**
     * Test constructor
     *
     * @covers ::__construct
     * @return void
     */
    public function testConstructor(): void
    {
        $this->assertInstanceOf(EventAction::class, $this->eventAction);
    }

    /**
     * Test run method with empty arguments
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithEmptyArguments(): void
    {
        $arguments = [];
        
        $result = $this->eventAction->run($arguments);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test run method with default arguments
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithDefaultArguments(): void
    {
        $result = $this->eventAction->run();
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test run method with various argument types
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithVariousArguments(): void
    {
        $testCases = [
            [],
            ['key' => 'value'],
            ['event' => 'test', 'data' => ['id' => 1]],
            ['sourceId' => 123, 'eventType' => 'create'],
            ['multiple' => 'values', 'nested' => ['array' => 'data']]
        ];

        foreach ($testCases as $arguments) {
            $result = $this->eventAction->run($arguments);
            
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        }
    }

    /**
     * Test run method with empty array arguments
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithEmptyArrayArguments(): void
    {
        $result = $this->eventAction->run([]);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }


    /**
     * Test run method return type consistency
     *
     * @covers ::run
     * @return void
     */
    public function testRunReturnTypeConsistency(): void
    {
        $result1 = $this->eventAction->run();
        $result2 = $this->eventAction->run([]);
        $result3 = $this->eventAction->run(['test' => 'value']);
        
        $this->assertIsArray($result1);
        $this->assertIsArray($result2);
        $this->assertIsArray($result3);
        
        $this->assertEquals($result1, $result2);
        $this->assertEquals($result1, $result3);
    }

    /**
     * Test run method with large argument arrays
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithLargeArgumentArrays(): void
    {
        $largeArray = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeArray["key_$i"] = "value_$i";
        }
        
        $result = $this->eventAction->run($largeArray);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test run method with nested arrays
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithNestedArrays(): void
    {
        $nestedArray = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'data' => 'value'
                    ]
                ]
            ],
            'simple' => 'value',
            'array' => [1, 2, 3, 4, 5]
        ];
        
        $result = $this->eventAction->run($nestedArray);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test run method with special characters in arguments
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithSpecialCharacters(): void
    {
        $specialArray = [
            'unicode' => '测试中文',
            'special' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
            'quotes' => '"double" and \'single\'',
            'newlines' => "line1\nline2\rline3",
            'tabs' => "col1\tcol2\tcol3"
        ];
        
        $result = $this->eventAction->run($specialArray);
        
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test run method performance with multiple calls
     *
     * @covers ::run
     * @return void
     */
    public function testRunPerformanceWithMultipleCalls(): void
        {
        $startTime = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $result = $this->eventAction->run(['iteration' => $i]);
            $this->assertIsArray($result);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should complete within reasonable time (less than 1 second for 100 calls)
        $this->assertLessThan(1.0, $executionTime);
    }

    /**
     * Test run method with edge case arguments
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithEdgeCaseArguments(): void
    {
        $edgeCases = [
            ['' => 'empty_key'],
            ['key' => ''],
            [0 => 'zero_key'],
            ['key' => 0],
            [null => 'null_key'],
            ['key' => null],
            [false => 'false_key'],
            ['key' => false],
            [true => 'true_key'],
            ['key' => true]
        ];

        foreach ($edgeCases as $arguments) {
            $result = $this->eventAction->run($arguments);
            
            $this->assertIsArray($result);
            $this->assertEmpty($result);
        }
    }
}
