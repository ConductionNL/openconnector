<?php

declare(strict_types=1);

/**
 * TargetHandlerRegistryTest.php
 *
 * Unit tests for the TargetHandlerRegistry class.
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
use OCA\OpenConnector\Service\TargetHandler\ApiHandler;
use OCA\OpenConnector\Service\TargetHandler\OpenRegisterHandler;
use OCA\OpenConnector\Service\TargetHandler\TargetHandlerInterface;
use OCA\OpenConnector\Service\TargetHandler\TargetHandlerRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

/**
 * Class TargetHandlerRegistryTest
 *
 * Unit tests for the TargetHandlerRegistry class.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Service\TargetHandler
 * @author    Conduction <info@conduction.nl>
 * @license   EUPL-1.2
 * @link      https://github.com/nextcloud/server/tree/master/apps/openconnector
 */
class TargetHandlerRegistryTest extends TestCase
{
    /**
     * The container mock.
     *
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $container;

    /**
     * The target handler registry.
     *
     * @var TargetHandlerRegistry
     */
    private $registry;

    /**
     * Set up the test environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->container = $this->createMock(ContainerInterface::class);
        $this->registry = new TargetHandlerRegistry($this->container);
    }

    /**
     * Test registering and getting handlers.
     *
     * @return void
     */
    public function testRegisterAndGetHandlers(): void
    {
        // Create mock handlers
        $apiHandler = $this->createMock(TargetHandlerInterface::class);
        $apiHandler->method('canHandle')
            ->with('api')
            ->willReturn(true);

        $openRegisterHandler = $this->createMock(TargetHandlerInterface::class);
        $openRegisterHandler->method('canHandle')
            ->with('register/object')
            ->willReturn(true);

        // Register handlers
        $this->registry->registerHandler($apiHandler);
        $this->registry->registerHandler($openRegisterHandler);

        // Test getHandlers method
        $handlers = $this->registry->getHandlers();
        $this->assertCount(2, $handlers);
        $this->assertSame($apiHandler, $handlers[0]);
        $this->assertSame($openRegisterHandler, $handlers[1]);
    }

    /**
     * Test getting a handler for a specific type.
     *
     * @return void
     */
    public function testGetHandlerForType(): void
    {
        // Create mock handlers
        $apiHandler = $this->createMock(TargetHandlerInterface::class);
        $apiHandler->method('canHandle')
            ->willReturnCallback(function($type) {
                return $type === 'api';
            });

        $openRegisterHandler = $this->createMock(TargetHandlerInterface::class);
        $openRegisterHandler->method('canHandle')
            ->willReturnCallback(function($type) {
                return $type === 'register/object';
            });

        // Register handlers
        $this->registry->registerHandler($apiHandler);
        $this->registry->registerHandler($openRegisterHandler);

        // Test getHandlerForType method
        $handler = $this->registry->getHandlerForType('api');
        $this->assertSame($apiHandler, $handler);

        $handler = $this->registry->getHandlerForType('register/object');
        $this->assertSame($openRegisterHandler, $handler);
    }

    /**
     * Test getting a handler for an unknown type.
     *
     * @return void
     */
    public function testGetHandlerForUnknownType(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No suitable target handler found for type: unknown');

        $this->registry->getHandlerForType('unknown');
    }

    /**
     * Test getting a handler from the synchronization.
     *
     * @return void
     */
    public function testGetHandlerFromSynchronization(): void
    {
        // Create mock synchronization
        $synchronization = new Synchronization();
        $synchronization->setTargetType('api');

        // Create mock handler
        $apiHandler = $this->createMock(ApiHandler::class);
        $apiHandler->method('getSupportedType')->willReturn('api');

        // Register handler
        $this->registry->registerHandler($apiHandler);

        // Test getHandlerFromSynchronization method
        $handler = $this->registry->getHandlerFromSynchronization($synchronization);
        $this->assertSame($apiHandler, $handler);
    }

    /**
     * Test deleting invalid objects.
     *
     * @return void
     */
    public function testDeleteInvalidObjects(): void
    {
        // Create mock synchronization
        $synchronization = new Synchronization();
        $synchronization->setTargetType('api');

        // Create mock handler
        $apiHandler = $this->createMock(ApiHandler::class);
        $apiHandler->method('getSupportedType')->willReturn('api');
        $apiHandler->expects($this->once())
            ->method('deleteInvalidObjects')
            ->with($this->equalTo($synchronization), $this->equalTo(['target-1', 'target-2']))
            ->willReturn(3);

        // Register handler
        $this->registry->registerHandler($apiHandler);

        // Test deleteInvalidObjects method
        $result = $this->registry->deleteInvalidObjects($synchronization, ['target-1', 'target-2']);
        $this->assertEquals(3, $result);
    }
} 