<?php

declare(strict_types=1);

/**
 * SynchronizationActionTest
 *
 * Comprehensive unit tests for the SynchronizationAction class to verify
 * synchronization functionality and error handling.
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

use OCA\OpenConnector\Action\SynchronizationAction;
use OCA\OpenConnector\Service\SynchronizationService;
use OCA\OpenConnector\Db\SynchronizationMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationContract;
use Exception;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Synchronization Action Test Suite
 *
 * Comprehensive unit tests for synchronization action functionality including
 * execution, error handling, and response generation.
 *
 * @coversDefaultClass SynchronizationAction
 */
class SynchronizationActionTest extends TestCase
{
    private SynchronizationAction $synchronizationAction;
    private SynchronizationService|MockObject $synchronizationService;
    private SynchronizationMapper|MockObject $synchronizationMapper;
    private SynchronizationContractMapper|MockObject $synchronizationContractMapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->synchronizationService = $this->createMock(SynchronizationService::class);
        $this->synchronizationMapper = $this->createMock(SynchronizationMapper::class);
        $this->synchronizationContractMapper = $this->createMock(SynchronizationContractMapper::class);
        
        $this->synchronizationAction = new SynchronizationAction(
            $this->synchronizationService,
            $this->synchronizationMapper,
            $this->synchronizationContractMapper
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
        $this->assertInstanceOf(SynchronizationAction::class, $this->synchronizationAction);
    }

    /**
     * Test run method with valid synchronizationId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithValidSynchronizationId(): void
    {
        $synchronizationId = 123;
        $arguments = ['synchronizationId' => $synchronizationId];
        
        $synchronization = $this->getMockBuilder(Synchronization::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $synchronization->id = $synchronizationId;
        
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willReturn($synchronization);
        
        $this->synchronizationService->expects($this->once())
            ->method('synchronize')
            ->with($synchronization)
            ->willReturn(['result' => ['objects' => ['found' => 5], 'contracts' => null]]);
        
        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('INFO', $result['level']);
        $this->assertContains('Check for a valid synchronization ID', $result['stackTrace']);
        $this->assertContains("Getting synchronization: {$synchronizationId}", $result['stackTrace']);
        $this->assertContains('Doing the synchronization', $result['stackTrace']);
        $this->assertContains('Synchronized 5 successfully', $result['stackTrace']);
    }

    /**
     * Test run method with string synchronizationId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithStringSynchronizationId(): void
    {
        $synchronizationId = '123';
        $arguments = ['synchronizationId' => $synchronizationId];
        
        $synchronization = $this->getMockBuilder(Synchronization::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $synchronization->id = 123;
        
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($synchronization);
        
        $this->synchronizationService->expects($this->once())
            ->method('synchronize')
            ->with($synchronization)
            ->willReturn(['result' => ['objects' => ['found' => 3], 'contracts' => null]]);
        
        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('INFO', $result['level']);
        $this->assertContains('Synchronized 3 successfully', $result['stackTrace']);
    }

    /**
     * Test run method without synchronizationId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithoutSynchronizationId(): void
    {
        $arguments = [];

        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('ERROR', $result['level']);
        $this->assertContains('Check for a valid synchronization ID', $result['stackTrace']);
        $this->assertContains('No synchronization ID provided', $result['stackTrace']);
    }

    /**
     * Test run method with null synchronizationId
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithNullSynchronizationId(): void
    {
        $arguments = ['synchronizationId' => null];

        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('ERROR', $result['level']);
        $this->assertContains('No synchronization ID provided', $result['stackTrace']);
    }

    /**
     * Test run method with synchronization not found
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithSynchronizationNotFound(): void
    {
        $synchronizationId = 999;
        $arguments = ['synchronizationId' => $synchronizationId];
        
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willThrowException(new \OCP\AppFramework\Db\DoesNotExistException('Synchronization not found'));
        
        // The current implementation doesn't catch the DoesNotExistException, so it will be thrown
        $this->expectException(\OCP\AppFramework\Db\DoesNotExistException::class);
        $this->expectExceptionMessage('Synchronization not found');
        
        $this->synchronizationAction->run($arguments);
    }

    /**
     * Test run method with exception during synchronization
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithExceptionDuringSynchronization(): void
    {
        $synchronizationId = 123;
        $arguments = ['synchronizationId' => $synchronizationId];
        
        $synchronization = $this->getMockBuilder(Synchronization::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $synchronization->id = $synchronizationId;
        
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willReturn($synchronization);
        
        $this->synchronizationService->expects($this->once())
            ->method('synchronize')
            ->with($synchronization)
            ->willThrowException(new Exception('Synchronization failed'));
        
        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('ERROR', $result['level']);
        $this->assertContains('Doing the synchronization', $result['stackTrace']);
        $this->assertContains('Failed to synchronize: Synchronization failed', $result['stackTrace']);
    }

    /**
     * Test run method with TooManyRequestsHttpException
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithTooManyRequestsHttpException(): void
    {
        $synchronizationId = 123;
        $arguments = ['synchronizationId' => $synchronizationId];
        
        $synchronization = $this->getMockBuilder(Synchronization::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $synchronization->id = $synchronizationId;
        
        $exception = new TooManyRequestsHttpException(60, 'Rate limit exceeded');
        
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willReturn($synchronization);
        
        $this->synchronizationService->expects($this->once())
            ->method('synchronize')
            ->with($synchronization)
            ->willThrowException($exception);
        
        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('WARNING', $result['level']);
        $this->assertContains('Doing the synchronization', $result['stackTrace']);
        $this->assertContains('Stopped synchronization: Rate limit exceeded', $result['stackTrace']);
    }

    /**
     * Test run method with contracts result
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithContractsResult(): void
    {
        $synchronizationId = 123;
        $arguments = ['synchronizationId' => $synchronizationId];
        
        $synchronization = $this->getMockBuilder(Synchronization::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $synchronization->id = $synchronizationId;
        
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willReturn($synchronization);
        
        $this->synchronizationService->expects($this->once())
            ->method('synchronize')
            ->with($synchronization)
            ->willReturn(['result' => ['contracts' => ['contract1', 'contract2', 'contract3']]]);
        
        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('INFO', $result['level']);
        $this->assertContains('Synchronized 3 successfully', $result['stackTrace']);
    }

    /**
     * Test run method with additional arguments
     *
     * @covers ::run
     * @return void
     */
    public function testRunWithAdditionalArguments(): void
    {
        $synchronizationId = 123;
        $arguments = [
            'synchronizationId' => $synchronizationId,
            'force' => true,
            'async' => false
        ];
        
        $synchronization = $this->getMockBuilder(Synchronization::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
        $synchronization->id = $synchronizationId;
        
        $this->synchronizationMapper->expects($this->once())
            ->method('find')
            ->with($synchronizationId)
            ->willReturn($synchronization);
        
        $this->synchronizationService->expects($this->once())
            ->method('synchronize')
            ->with($synchronization)
            ->willReturn(['result' => ['objects' => ['found' => 10], 'contracts' => null]]);
        
        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('INFO', $result['level']);
        $this->assertContains('Synchronized 10 successfully', $result['stackTrace']);
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

        $result = $this->synchronizationAction->run($arguments);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('stackTrace', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('level', $result);
        $this->assertEquals('ERROR', $result['level']);
        $this->assertContains('No synchronization ID provided', $result['stackTrace']);
    }
}