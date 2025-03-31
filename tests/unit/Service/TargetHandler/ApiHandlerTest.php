<?php

declare(strict_types=1);

/**
 * ApiHandlerTest.php
 *
 * Unit tests for the ApiHandler class.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Service\TargetHandler
 * @author    Conduction <info@conduction.nl>
 * @copyright 2023 Conduction
 * @license   https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12 EUPL-1.2
 * @version   GIT: <git_id>
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service\TargetHandler;

use Exception;
use OCA\OpenConnector\Db\Synchronization;
use OCA\OpenConnector\Db\SynchronizationContract;
use OCA\OpenConnector\Db\SourceMapper;
use OCA\OpenConnector\Db\SynchronizationContractMapper;
use OCA\OpenConnector\Service\CallService;
use OCA\OpenConnector\Service\TargetHandler\ApiHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class ApiHandlerTest
 *
 * Unit tests for the ApiHandler class.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Service\TargetHandler
 * @author    Conduction <info@conduction.nl>
 * @license   EUPL-1.2
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */
class ApiHandlerTest extends TestCase
{
    /**
     * The call service mock.
     *
     * @var CallService|\PHPUnit\Framework\MockObject\MockObject
     */
    private $callService;

    /**
     * The handler to test.
     *
     * @var ApiHandler
     */
    private $handler;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for all dependencies
        $this->callService = $this->createMock(CallService::class);
        $sourceMapper = $this->createMock(SourceMapper::class);
        $synchronizationContractMapper = $this->createMock(SynchronizationContractMapper::class);
        $container = $this->createMock(ContainerInterface::class);

        // Create the handler to test
        $this->handler = new ApiHandler(
            $this->callService,
            $sourceMapper,
            $synchronizationContractMapper,
            $container
        );
    }

    /**
     * Test the getSupportedType method.
     *
     * @return void
     */
    public function testGetSupportedType(): void
    {
        $this->assertEquals('api', $this->handler->getSupportedType());
    }

    /**
     * Test the createObject method.
     *
     * @return void
     * 
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testCreateObject(): void
    {
        // Create mock objects
        $synchronization = new Synchronization();
        $synchronization->setTargetConfig([
            'endpoint' => 'https://api.example.com/objects',
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json'],
            'idPath' => 'data.id',
        ]);

        $data = ['name' => 'Test Object', 'description' => 'This is a test object'];

        $apiResponse = [
            'status' => 'success',
            'data' => [
                'id' => 'obj-123',
                'name' => 'Test Object',
                'description' => 'This is a test object',
                'created_at' => '2023-01-01T12:00:00Z'
            ]
        ];

        // Configure mocks
        $this->callService->expects($this->once())
            ->method('applyConfigDot')
            ->with($synchronization->getTargetConfig())
            ->willReturn($synchronization->getTargetConfig());

        $this->callService->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo('https://api.example.com/objects'),
                $this->equalTo('POST'),
                $this->equalTo(['Content-Type' => 'application/json']),
                $this->equalTo([]),
                $this->equalTo($data)
            )
            ->willReturn($apiResponse);

        // Call the method
        $result = $this->handler->createObject($synchronization, $data);

        // Assert the result
        $this->assertEquals('obj-123', $result['id']);
        $this->assertEquals($apiResponse, $result);
    }

    /**
     * Test the updateObject method.
     *
     * @return void
     * 
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testUpdateObject(): void
    {
        // Create mock objects
        $synchronization = new Synchronization();
        $synchronization->setTargetConfig([
            'endpoint' => 'https://api.example.com/objects/{id}',
            'method' => 'PUT',
            'headers' => ['Content-Type' => 'application/json'],
            'idPath' => 'data.id',
        ]);

        $contract = new SynchronizationContract();
        $contract->setTargetId('obj-123');

        $data = [
            'id' => 'obj-123',
            'name' => 'Updated Object',
            'description' => 'This is an updated test object'
        ];

        $apiResponse = [
            'status' => 'success',
            'data' => [
                'id' => 'obj-123',
                'name' => 'Updated Object',
                'description' => 'This is an updated test object',
                'updated_at' => '2023-01-02T12:00:00Z'
            ]
        ];

        // Configure mocks
        $this->callService->expects($this->once())
            ->method('applyConfigDot')
            ->with($synchronization->getTargetConfig())
            ->willReturn($synchronization->getTargetConfig());

        $this->callService->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo('https://api.example.com/objects/obj-123'),
                $this->equalTo('PUT'),
                $this->equalTo(['Content-Type' => 'application/json']),
                $this->equalTo([]),
                $this->equalTo($data)
            )
            ->willReturn($apiResponse);

        // Call the method
        $result = $this->handler->updateObject($synchronization, $contract, $data);

        // Assert the result
        $this->assertEquals('obj-123', $result['data']['id']);
        $this->assertEquals($apiResponse, $result);
    }

    /**
     * Test the deleteObject method.
     *
     * @return void
     * 
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testDeleteObject(): void
    {
        // Create mock objects
        $synchronization = new Synchronization();
        $synchronization->setTargetConfig([
            'endpoint' => 'https://api.example.com/objects/{id}',
            'method' => 'DELETE',
            'headers' => ['Content-Type' => 'application/json'],
        ]);

        $contract = new SynchronizationContract();
        $contract->setTargetId('obj-123');

        $apiResponse = [
            'status' => 'success',
            'message' => 'Object deleted successfully'
        ];

        // Configure mocks
        $this->callService->expects($this->once())
            ->method('applyConfigDot')
            ->with($synchronization->getTargetConfig())
            ->willReturn($synchronization->getTargetConfig());

        $this->callService->expects($this->once())
            ->method('call')
            ->with(
                $this->equalTo('https://api.example.com/objects/obj-123'),
                $this->equalTo('DELETE'),
                $this->equalTo(['Content-Type' => 'application/json']),
                $this->equalTo([]),
                $this->equalTo(null)
            )
            ->willReturn($apiResponse);

        // Call the method
        $result = $this->handler->deleteObject($synchronization, $contract);

        // Assert the result
        $this->assertEquals($apiResponse, $result);
    }

    /**
     * Test the objectHasChanged method when the object has changed.
     *
     * @return void
     */
    public function testObjectHasChangedTrue(): void
    {
        // Create mock objects with different data
        $contract = new SynchronizationContract();
        $contract->setData([
            'name' => 'Original Name',
            'value' => 123
        ]);

        $data = [
            'name' => 'New Name',
            'value' => 123
        ];

        // Call the method
        $result = $this->handler->objectHasChanged($contract, $data);

        // Assert the result
        $this->assertTrue($result);
    }

    /**
     * Test the objectHasChanged method when the object hasn't changed.
     *
     * @return void
     */
    public function testObjectHasChangedFalse(): void
    {
        // Create mock objects with same data
        $contract = new SynchronizationContract();
        $contract->setData([
            'name' => 'Original Name',
            'value' => 123
        ]);

        $data = [
            'name' => 'Original Name',
            'value' => 123
        ];

        // Call the method
        $result = $this->handler->objectHasChanged($contract, $data);

        // Assert the result
        $this->assertFalse($result);
    }

    /**
     * Test the deleteInvalidObjects method.
     *
     * @return void
     * 
     * @throws \OCP\DB\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function testDeleteInvalidObjects(): void
    {
        // For now, this is just a placeholder test since we would need to mock
        // a lot of database interactions to properly test this method
        $this->assertTrue(true);
    }
} 