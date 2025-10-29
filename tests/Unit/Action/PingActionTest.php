<?php

declare(strict_types=1);

/**
 * PingActionTest
 *
 * Comprehensive unit tests for the PingAction class to verify ping action
 * functionality and API call execution.
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

use OCA\OpenConnector\Action\PingAction;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\Source;
use OCA\OpenConnector\Db\CallLog;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Ping Action Test Suite
 *
 * Comprehensive unit tests for ping action functionality including
 * API call execution, source handling, and response generation.
 *
 * @coversDefaultClass PingAction
 */
class PingActionTest extends TestCase
{
    private PingAction $pingAction;
    private CallService|MockObject $callService;
    private SourceMapper|MockObject $sourceMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->callService = $this->createMock(CallService::class);
        $this->sourceMapper = $this->createMock(SourceMapper::class);
        
        $this->pingAction = new PingAction(
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
        $this->assertInstanceOf(PingAction::class, $this->pingAction);
    }

    /**
     * Test run method with valid sourceId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithValidSourceId(): void
    {
        $sourceId = 123;
        $arguments = ['sourceId' => $sourceId];
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = $sourceId;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with($sourceId)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains("Found sourceId {$sourceId} in arguments", $result['stackTrace']);
        $this->assertContains('Calling callService...', $result['stackTrace']);
        $this->assertContains('Created callLog with id: 456', $result['stackTrace']);
    }

    /**
     * Test run method with string sourceId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithStringSourceId(): void
    {
        $sourceId = '123';
        $arguments = ['sourceId' => $sourceId];
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = 123;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains("Found sourceId {$sourceId} in arguments", $result['stackTrace']);
    }

    /**
     * Test run method without sourceId (defaults to sourceId = 1)
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithoutSourceId(): void
    {
        $arguments = [];
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = 1;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains('No sourceId in arguments, default to sourceId = 1', $result['stackTrace']);
    }

    /**
     * Test run method with invalid sourceId (non-numeric)
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithInvalidSourceId(): void
    {
        $sourceId = 'invalid';
        $arguments = ['sourceId' => $sourceId];
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = 0;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        // When sourceId is invalid (non-numeric), (int) 'invalid' becomes 0
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with(0)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains("Found sourceId {$sourceId} in arguments", $result['stackTrace']);
    }

    /**
     * Test run method with null sourceId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithNullSourceId(): void
    {
        $arguments = ['sourceId' => null];
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = 1;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        // When sourceId is null, it defaults to sourceId = 1
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains('No sourceId in arguments, default to sourceId = 1', $result['stackTrace']);
    }

    /**
     * Test run method with zero sourceId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithZeroSourceId(): void
    {
        $sourceId = 0;
        $arguments = ['sourceId' => $sourceId];
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = 0;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with(0)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains("Found sourceId {$sourceId} in arguments", $result['stackTrace']);
    }

    /**
     * Test run method with negative sourceId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithNegativeSourceId(): void
    {
        $sourceId = -1;
        $arguments = ['sourceId' => $sourceId];
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = -1;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with(-1)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains("Found sourceId {$sourceId} in arguments", $result['stackTrace']);
    }

    /**
     * Test run method with additional arguments
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithAdditionalArguments(): void
    {
        $sourceId = 123;
        $arguments = [
            'sourceId' => $sourceId,
            'timeout' => 30,
            'retries' => 3
        ];
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = $sourceId;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with($sourceId)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains("Found sourceId {$sourceId} in arguments", $result['stackTrace']);
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
        
        $source = $this->getMockBuilder(Source::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $source->id = 1;
        
        $callLog = $this->getMockBuilder(CallLog::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $callLog->id = 456;
        
        $this->sourceMapper->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($source);
        
        $this->callService->expects($this->once())
            ->method('call')
            ->with($source)
            ->willReturn($callLog);

        $result = $this->pingAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertContains('Running PingAction', $result['stackTrace']);
        $this->assertContains('No sourceId in arguments, default to sourceId = 1', $result['stackTrace']);
    }
}