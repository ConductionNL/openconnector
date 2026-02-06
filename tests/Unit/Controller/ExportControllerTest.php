<?php

declare(strict_types=1);

/**
 * ExportControllerTest
 * 
 * Unit tests for the ExportController
 *
 * @category   Test
 * @package    OCA\OpenConnector\Tests\Unit\Controller
 * @author     Conduction.nl <info@conduction.nl>
 * @copyright  Conduction.nl 2024
 * @license    EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version    1.0.0
 * @link       https://github.com/ConductionNL/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Controller;

use OCA\OpenConnector\Controller\ExportController;
use OCA\OpenConnector\Service\ExportService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the ExportController
 *
 * This test class covers all functionality of the ExportController
 * including object export operations with different accept headers.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class ExportControllerTest extends TestCase
{
    /**
     * The ExportController instance being tested
     *
     * @var ExportController
     */
    private ExportController $controller;

    /**
     * Mock request object
     *
     * @var MockObject|IRequest
     */
    private MockObject $request;

    /**
     * Mock app config
     *
     * @var MockObject|IAppConfig
     */
    private MockObject $config;

    /**
     * Mock export service
     *
     * @var MockObject|ExportService
     */
    private MockObject $exportService;

    /**
     * Set up test environment before each test
     *
     * This method initializes all mocks and the controller instance
     * for testing purposes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects for all dependencies
        $this->request = $this->createMock(IRequest::class);
        $this->config = $this->createMock(IAppConfig::class);
        $this->exportService = $this->createMock(ExportService::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new ExportController(
            'openconnector',
            $this->request,
            $this->config,
            $this->exportService
        );
    }

    /**
     * Test successful export with JSON accept header
     *
     * This test verifies that the export() method handles JSON export correctly.
     *
     * @return void
     */
    public function testExportWithJsonAcceptHeader(): void
    {
        $type = 'user';
        $id = '123';
        $accept = 'application/json';

        $expectedResponse = new JSONResponse([
            'id' => '123',
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        // Mock request to return accept header
        $this->request->expects($this->once())
            ->method('getHeader')
            ->with('Accept')
            ->willReturn($accept);

        // Mock export service to return success response
        $this->exportService->expects($this->once())
            ->method('export')
            ->with($type, $id, $accept)
            ->willReturn($expectedResponse);

        // Execute the method
        $response = $this->controller->export($type, $id);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedResponse->getData(), $response->getData());
    }

    /**
     * Test successful export with XML accept header
     *
     * This test verifies that the export() method handles XML export correctly.
     *
     * @return void
     */
    public function testExportWithXmlAcceptHeader(): void
    {
        $type = 'user';
        $id = '123';
        $accept = 'application/xml';

        $expectedResponse = new JSONResponse([
            'content' => '<user><id>123</id><name>John Doe</name></user>',
            'contentType' => 'application/xml'
        ]);

        // Mock request to return accept header
        $this->request->expects($this->once())
            ->method('getHeader')
            ->with('Accept')
            ->willReturn($accept);

        // Mock export service to return success response
        $this->exportService->expects($this->once())
            ->method('export')
            ->with($type, $id, $accept)
            ->willReturn($expectedResponse);

        // Execute the method
        $response = $this->controller->export($type, $id);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedResponse->getData(), $response->getData());
    }

    /**
     * Test successful export with CSV accept header
     *
     * This test verifies that the export() method handles CSV export correctly.
     *
     * @return void
     */
    public function testExportWithCsvAcceptHeader(): void
    {
        $type = 'user';
        $id = '123';
        $accept = 'text/csv';

        $expectedResponse = new JSONResponse([
            'content' => 'id,name,email\n123,John Doe,john@example.com',
            'contentType' => 'text/csv'
        ]);

        // Mock request to return accept header
        $this->request->expects($this->once())
            ->method('getHeader')
            ->with('Accept')
            ->willReturn($accept);

        // Mock export service to return success response
        $this->exportService->expects($this->once())
            ->method('export')
            ->with($type, $id, $accept)
            ->willReturn($expectedResponse);

        // Execute the method
        $response = $this->controller->export($type, $id);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedResponse->getData(), $response->getData());
    }

    /**
     * Test export with missing accept header
     *
     * This test verifies that the export() method returns an error response
     * when the Accept header is missing.
     *
     * @return void
     */
    public function testExportWithMissingAcceptHeader(): void
    {
        $type = 'user';
        $id = '123';

        // Mock request to return empty accept header
        $this->request->expects($this->once())
            ->method('getHeader')
            ->with('Accept')
            ->willReturn('');

        // Execute the method
        $response = $this->controller->export($type, $id);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Request is missing header Accept'], $response->getData());
        $this->assertEquals(400, $response->getStatus());
    }

    /**
     * Test export with empty accept header
     *
     * This test verifies that the export() method returns an error response
     * when the Accept header is empty.
     *
     * @return void
     */
    public function testExportWithEmptyAcceptHeader(): void
    {
        $type = 'user';
        $id = '123';

        // Mock request to return empty accept header
        $this->request->expects($this->once())
            ->method('getHeader')
            ->with('Accept')
            ->willReturn('');

        // Execute the method
        $response = $this->controller->export($type, $id);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Request is missing header Accept'], $response->getData());
        $this->assertEquals(400, $response->getStatus());
    }

    /**
     * Test export with different object types
     *
     * This test verifies that the export() method handles different object types correctly.
     *
     * @return void
     */
    public function testExportWithDifferentObjectTypes(): void
    {
        $types = ['user', 'group', 'file', 'document'];
        $id = '123';
        $accept = 'application/json';

        foreach ($types as $type) {
            $expectedResponse = new JSONResponse([
                'id' => '123',
                'type' => $type,
                'data' => 'exported_data'
            ]);

            // Mock request to return accept header
            $this->request->expects($this->once())
                ->method('getHeader')
                ->with('Accept')
                ->willReturn($accept);

            // Mock export service to return success response
            $this->exportService->expects($this->once())
                ->method('export')
                ->with($type, $id, $accept)
                ->willReturn($expectedResponse);

            // Execute the method
            $response = $this->controller->export($type, $id);

            // Assert response is successful
            $this->assertInstanceOf(JSONResponse::class, $response);
            $this->assertEquals($expectedResponse->getData(), $response->getData());

            // Reset mocks for next iteration
            $this->setUp();
        }
    }

    /**
     * Test export with different IDs
     *
     * This test verifies that the export() method handles different IDs correctly.
     *
     * @return void
     */
    public function testExportWithDifferentIds(): void
    {
        $type = 'user';
        $ids = ['123', '456', '789'];
        $accept = 'application/json';

        foreach ($ids as $id) {
            $expectedResponse = new JSONResponse([
                'id' => $id,
                'name' => 'User ' . $id,
                'data' => 'exported_data'
            ]);

            // Mock request to return accept header
            $this->request->expects($this->once())
                ->method('getHeader')
                ->with('Accept')
                ->willReturn($accept);

            // Mock export service to return success response
            $this->exportService->expects($this->once())
                ->method('export')
                ->with($type, $id, $accept)
                ->willReturn($expectedResponse);

            // Execute the method
            $response = $this->controller->export($type, $id);

            // Assert response is successful
            $this->assertInstanceOf(JSONResponse::class, $response);
            $this->assertEquals($expectedResponse->getData(), $response->getData());

            // Reset mocks for next iteration
            $this->setUp();
        }
    }

    /**
     * Test export with complex accept header
     *
     * This test verifies that the export() method handles complex accept headers correctly.
     *
     * @return void
     */
    public function testExportWithComplexAcceptHeader(): void
    {
        $type = 'user';
        $id = '123';
        $accept = 'application/json, application/xml;q=0.9, text/csv;q=0.8';

        $expectedResponse = new JSONResponse([
            'id' => '123',
            'name' => 'John Doe',
            'format' => 'json'
        ]);

        // Mock request to return accept header
        $this->request->expects($this->once())
            ->method('getHeader')
            ->with('Accept')
            ->willReturn($accept);

        // Mock export service to return success response
        $this->exportService->expects($this->once())
            ->method('export')
            ->with($type, $id, $accept)
            ->willReturn($expectedResponse);

        // Execute the method
        $response = $this->controller->export($type, $id);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedResponse->getData(), $response->getData());
    }
}
