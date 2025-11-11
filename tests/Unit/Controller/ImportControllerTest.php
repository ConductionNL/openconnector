<?php

declare(strict_types=1);

/**
 * ImportControllerTest
 * 
 * Unit tests for the ImportController
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

use OCA\OpenConnector\Controller\ImportController;
use OCA\OpenConnector\Service\ImportService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the ImportController
 *
 * This test class covers all functionality of the ImportController
 * including file import operations with single and multiple files.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class ImportControllerTest extends TestCase
{
    /**
     * The ImportController instance being tested
     *
     * @var ImportController
     */
    private ImportController $controller;

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
     * Mock import service
     *
     * @var MockObject|ImportService
     */
    private MockObject $importService;

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
        $this->importService = $this->createMock(ImportService::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new ImportController(
            'openconnector',
            $this->request,
            $this->config,
            $this->importService
        );
    }

    /**
     * Test successful import with single file
     *
     * This test verifies that the import() method handles single file uploads correctly.
     *
     * @return void
     */
    public function testImportWithSingleFile(): void
    {
        $importData = [
            'source' => 'test_source',
            'target' => 'test_target'
        ];

        $uploadedFile = [
            'name' => 'test.json',
            'type' => 'application/json',
            'tmp_name' => '/tmp/test.json',
            'error' => 0,
            'size' => 1024
        ];

        $expectedResponse = new JSONResponse(['message' => 'Import successful']);

        // Mock request to return import data and uploaded file
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($importData);

        $this->request->expects($this->once())
            ->method('getUploadedFile')
            ->with('file')
            ->willReturn($uploadedFile);

        // Mock import service to return success response
        $this->importService->expects($this->once())
            ->method('import')
            ->with($importData, [$uploadedFile])
            ->willReturn($expectedResponse);

        // Mock global $_FILES to be empty
        $GLOBALS['_FILES'] = [];

        // Execute the method
        $response = $this->controller->import();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Import successful'], $response->getData());
    }

    /**
     * Test successful import with multiple files
     *
     * This test verifies that the import() method handles multiple file uploads correctly.
     *
     * @return void
     */
    public function testImportWithMultipleFiles(): void
    {
        $importData = [
            'source' => 'test_source',
            'target' => 'test_target'
        ];

        $multipleFiles = [
            'name' => ['file1.json', 'file2.json'],
            'type' => ['application/json', 'application/json'],
            'tmp_name' => ['/tmp/file1.json', '/tmp/file2.json'],
            'error' => [0, 0],
            'size' => [1024, 2048]
        ];

        $expectedUploadedFiles = [
            [
                'name' => 'file1.json',
                'type' => 'application/json',
                'tmp_name' => '/tmp/file1.json',
                'error' => 0,
                'size' => 1024
            ],
            [
                'name' => 'file2.json',
                'type' => 'application/json',
                'tmp_name' => '/tmp/file2.json',
                'error' => 0,
                'size' => 2048
            ]
        ];

        $expectedResponse = new JSONResponse(['message' => 'Multiple files imported successfully']);

        // Mock request to return import data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($importData);

        // Mock request to return no single uploaded file
        $this->request->expects($this->once())
            ->method('getUploadedFile')
            ->with('file')
            ->willReturn(null);

        // Mock import service to return success response
        $this->importService->expects($this->once())
            ->method('import')
            ->with($importData, $expectedUploadedFiles)
            ->willReturn($expectedResponse);

        // Mock global $_FILES to contain multiple files
        $GLOBALS['_FILES'] = ['files' => $multipleFiles];

        // Execute the method
        $response = $this->controller->import();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Multiple files imported successfully'], $response->getData());
    }

    /**
     * Test successful import with both single file and multiple files
     *
     * This test verifies that the import() method handles both single file
     * and multiple files correctly when both are present.
     *
     * @return void
     */
    public function testImportWithBothSingleAndMultipleFiles(): void
    {
        $importData = [
            'source' => 'test_source',
            'target' => 'test_target'
        ];

        $uploadedFile = [
            'name' => 'single.json',
            'type' => 'application/json',
            'tmp_name' => '/tmp/single.json',
            'error' => 0,
            'size' => 512
        ];

        $multipleFiles = [
            'name' => ['file1.json', 'file2.json'],
            'type' => ['application/json', 'application/json'],
            'tmp_name' => ['/tmp/file1.json', '/tmp/file2.json'],
            'error' => [0, 0],
            'size' => [1024, 2048]
        ];

        $expectedUploadedFiles = [
            [
                'name' => 'file1.json',
                'type' => 'application/json',
                'tmp_name' => '/tmp/file1.json',
                'error' => 0,
                'size' => 1024
            ],
            [
                'name' => 'file2.json',
                'type' => 'application/json',
                'tmp_name' => '/tmp/file2.json',
                'error' => 0,
                'size' => 2048
            ],
            [
                'name' => 'single.json',
                'type' => 'application/json',
                'tmp_name' => '/tmp/single.json',
                'error' => 0,
                'size' => 512
            ]
        ];

        $expectedResponse = new JSONResponse(['message' => 'All files imported successfully']);

        // Mock request to return import data and uploaded file
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($importData);

        $this->request->expects($this->once())
            ->method('getUploadedFile')
            ->with('file')
            ->willReturn($uploadedFile);

        // Mock import service to return success response
        $this->importService->expects($this->once())
            ->method('import')
            ->with($importData, $expectedUploadedFiles)
            ->willReturn($expectedResponse);

        // Mock global $_FILES to contain multiple files
        $GLOBALS['_FILES'] = ['files' => $multipleFiles];

        // Execute the method
        $response = $this->controller->import();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'All files imported successfully'], $response->getData());
    }

    /**
     * Test successful import with no files
     *
     * This test verifies that the import() method handles the case
     * when no files are uploaded.
     *
     * @return void
     */
    public function testImportWithNoFiles(): void
    {
        $importData = [
            'source' => 'test_source',
            'target' => 'test_target'
        ];

        $expectedResponse = new JSONResponse(['message' => 'No files to import']);

        // Mock request to return import data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($importData);

        // Mock request to return no single uploaded file
        $this->request->expects($this->once())
            ->method('getUploadedFile')
            ->with('file')
            ->willReturn(null);

        // Mock import service to return success response
        $this->importService->expects($this->once())
            ->method('import')
            ->with($importData, [])
            ->willReturn($expectedResponse);

        // Mock global $_FILES to be empty
        $GLOBALS['_FILES'] = [];

        // Execute the method
        $response = $this->controller->import();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'No files to import'], $response->getData());
    }

    /**
     * Test import with empty import data
     *
     * This test verifies that the import() method handles empty import data correctly.
     *
     * @return void
     */
    public function testImportWithEmptyData(): void
    {
        $importData = [];

        $expectedResponse = new JSONResponse(['message' => 'Import completed with empty data']);

        // Mock request to return empty import data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($importData);

        // Mock request to return no single uploaded file
        $this->request->expects($this->once())
            ->method('getUploadedFile')
            ->with('file')
            ->willReturn(null);

        // Mock import service to return success response
        $this->importService->expects($this->once())
            ->method('import')
            ->with($importData, [])
            ->willReturn($expectedResponse);

        // Mock global $_FILES to be empty
        $GLOBALS['_FILES'] = [];

        // Execute the method
        $response = $this->controller->import();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'Import completed with empty data'], $response->getData());
    }

    /**
     * Test import with multiple files but empty files array
     *
     * This test verifies that the import() method handles the case
     * when $_FILES['files'] exists but is empty.
     *
     * @return void
     */
    public function testImportWithEmptyMultipleFiles(): void
    {
        $importData = [
            'source' => 'test_source',
            'target' => 'test_target'
        ];

        $emptyFiles = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => []
        ];

        $expectedResponse = new JSONResponse(['message' => 'No files to import']);

        // Mock request to return import data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($importData);

        // Mock request to return no single uploaded file
        $this->request->expects($this->once())
            ->method('getUploadedFile')
            ->with('file')
            ->willReturn(null);

        // Mock import service to return success response
        $this->importService->expects($this->once())
            ->method('import')
            ->with($importData, [])
            ->willReturn($expectedResponse);

        // Mock global $_FILES to contain empty files array
        $GLOBALS['_FILES'] = ['files' => $emptyFiles];

        // Execute the method
        $response = $this->controller->import();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['message' => 'No files to import'], $response->getData());
    }
}
