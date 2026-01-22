<?php

declare(strict_types=1);

/**
 * StorageServiceTest
 *
 * Comprehensive unit tests for the StorageService class to verify file storage,
 * upload handling, caching, and storage management functionality.
 *
 * @category  Test
 * @package   OCA\OpenConnector\Tests\Unit\Service
 * @author    Conduction <info@conduction.nl>
 * @copyright 2024 OpenConnector
 * @license   AGPL-3.0
 * @version   1.0.0
 * @link      https://github.com/OpenConnector/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Service\StorageService;
use OCP\Files\IRootFolder;
use OCP\IAppConfig;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IUser;
use OCP\Files\Folder;
use OCP\Files\File;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Storage Service Test Suite
 *
 * Comprehensive unit tests for storage functionality including file management,
 * upload processing, caching, and storage operations. This test class validates
 * the core storage capabilities of the OpenConnector application.
 *
 * @coversDefaultClass StorageService
 */
class StorageServiceTest extends TestCase
{
    private StorageService $storageService;
    private MockObject $rootFolder;
    private MockObject $config;
    private MockObject $cacheFactory;
    private MockObject $cache;
    private MockObject $userManager;
    private MockObject $userSession;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rootFolder = $this->createMock(IRootFolder::class);
        $this->config = $this->createMock(IAppConfig::class);
        $this->cacheFactory = $this->createMock(ICacheFactory::class);
        $this->cache = $this->createMock(ICache::class);
        $this->userManager = $this->createMock(IUserManager::class);
        $this->userSession = $this->createMock(IUserSession::class);

        $this->cacheFactory
            ->method('createDistributed')
            ->willReturn($this->cache);

        $this->storageService = new StorageService(
            $this->rootFolder,
            $this->config,
            $this->cacheFactory,
            $this->userManager,
            $this->userSession
        );
    }

    /**
     * Test storage service constants
     *
     * This test verifies that the storage service has the correct
     * constants defined for cache keys and configuration.
     *
     * @covers StorageService::CACHE_KEY
     * @covers StorageService::UPLOAD_TARGET_PATH
     * @covers StorageService::UPLOAD_TARGET_ID
     * @covers StorageService::NUMBER_OF_PARTS
     * @covers StorageService::APP_USER
     * @return void
     */
    public function testStorageServiceConstants(): void
    {
        $this->assertEquals('openconnector-upload', StorageService::CACHE_KEY);
        $this->assertEquals('upload-target-path', StorageService::UPLOAD_TARGET_PATH);
        $this->assertEquals('upload-target-id', StorageService::UPLOAD_TARGET_ID);
        $this->assertEquals('number-of-parts', StorageService::NUMBER_OF_PARTS);
        $this->assertEquals('OpenRegister', StorageService::APP_USER);
    }

    /**
     * Test storage service initialization
     *
     * This test verifies that the storage service initializes
     * correctly with its dependencies.
     *
     * @covers ::__construct
     * @return void
     */
    public function testStorageServiceInitialization(): void
    {
        $this->assertInstanceOf(StorageService::class, $this->storageService);
    }

    /**
     * Test file upload creation
     *
     * This test verifies that the storage service can create
     * file uploads correctly.
     *
     * @covers ::createUpload
     * @return void
     */
    public function testCreateUploadWithValidParameters(): void
    {
        $path = '/uploads';
        $fileName = 'test-file.txt';
        $fileSize = 1024;

        // Mock the config to return a valid part size
        $this->config
            ->method('getValueInt')
            ->with('openconnector', 'part-size', 1000000)
            ->willReturn(1000000);

        // Mock the user manager
        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->userManager->method('get')->willReturn($mockUser);

        // Mock the root folder and its methods
        $mockFolder = $this->createMock(Folder::class);
        $mockFile = $this->createMock(File::class);
        $mockFile->method('getId')->willReturn(1);
        $mockFolder->method('newFile')->willReturn($mockFile);
        $mockFolder->method('newFolder')->willReturn($mockFolder);
        $this->rootFolder->method('get')->willReturn($mockFolder);

        $this->cache
            ->expects($this->atLeastOnce())
            ->method('set')
            ->willReturn(true);

        $result = $this->storageService->createUpload($path, $fileName, $fileSize);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
    }

    /**
     * Test file upload creation with large file
     *
     * This test verifies that the storage service can create
     * uploads for large files that require multiple parts.
     *
     * @covers ::createUpload
     * @return void
     */
    public function testCreateUploadWithLargeFile(): void
    {
        $path = '/uploads';
        $fileName = 'large-file.txt';
        $fileSize = 2500000; // 2.5MB, should create 3 parts with 1MB part size

        // Mock the config to return a valid part size
        $this->config
            ->method('getValueInt')
            ->with('openconnector', 'part-size', 1000000)
            ->willReturn(1000000);

        // Mock the user manager
        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->userManager->method('get')->willReturn($mockUser);

        // Mock the root folder and its methods
        $mockFolder = $this->createMock(Folder::class);
        $mockFile = $this->createMock(File::class);
        $mockFile->method('getId')->willReturn(1);
        $mockFolder->method('newFile')->willReturn($mockFile);
        $mockFolder->method('newFolder')->willReturn($mockFolder);
        $this->rootFolder->method('get')->willReturn($mockFolder);

        $this->cache
            ->expects($this->exactly(3)) // Should create 3 parts
            ->method('set')
            ->willReturn(true);

        $result = $this->storageService->createUpload($path, $fileName, $fileSize);

        $this->assertIsArray($result);
        $this->assertCount(3, $result); // Should have 3 parts
        
        // Verify part structure
        foreach ($result as $part) {
            $this->assertArrayHasKey('id', $part);
            $this->assertArrayHasKey('size', $part);
            $this->assertArrayHasKey('order', $part);
            $this->assertArrayHasKey('object', $part);
            $this->assertArrayHasKey('successful', $part);
            $this->assertFalse($part['successful']); // Initially false
        }
    }

    /**
     * Test file upload creation with object ID
     *
     * This test verifies that the storage service can create
     * uploads with an optional object ID parameter.
     *
     * @covers ::createUpload
     * @return void
     */
    public function testCreateUploadWithObjectId(): void
    {
        $path = '/uploads';
        $fileName = 'test-file.txt';
        $fileSize = 1024;
        $objectId = 'test-object-123';

        // Mock the config to return a valid part size
        $this->config
            ->method('getValueInt')
            ->with('openconnector', 'part-size', 1000000)
            ->willReturn(1000000);

        // Mock the user manager
        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->userManager->method('get')->willReturn($mockUser);

        // Mock the root folder and its methods
        $mockFolder = $this->createMock(Folder::class);
        $mockFile = $this->createMock(File::class);
        $mockFile->method('getId')->willReturn(1);
        $mockFolder->method('newFile')->willReturn($mockFile);
        $mockFolder->method('newFolder')->willReturn($mockFolder);
        $this->rootFolder->method('get')->willReturn($mockFolder);

        $this->cache
            ->expects($this->atLeastOnce())
            ->method('set')
            ->willReturn(true);

        $result = $this->storageService->createUpload($path, $fileName, $fileSize, $objectId);

        $this->assertIsArray($result);
        $this->assertNotEmpty($result);
        
        // Verify object ID is set in the first part
        $this->assertEquals($objectId, $result[0]['object']);
    }

    /**
     * Test file writing functionality
     *
     * This test verifies that the storage service can write
     * files correctly.
     *
     * @covers ::writeFile
     * @return void
     */
    public function testWriteFileWithValidContent(): void
    {
        $path = '/test/path';
        $fileName = 'test.txt';
        $content = 'test content';

        // Mock user
        $mockUser = $this->createMock(IUser::class);
        $mockUser->method('getUID')->willReturn('test-user');
        $this->userSession->method('getUser')->willReturn($mockUser);

        // Mock user folder
        $mockUserFolder = $this->createMock(Folder::class);
        $this->rootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        // Mock upload folder
        $mockUploadFolder = $this->createMock(Folder::class);
        $mockUserFolder->method('get')->with($path)->willReturn($mockUploadFolder);

        // Mock target file - simulate file not found, so it creates a new one
        $mockTargetFile = $this->createMock(File::class);
        $mockUploadFolder->method('get')->with($fileName)->willThrowException(new \OCP\Files\NotFoundException());
        $mockUploadFolder->method('newFile')->with($fileName, $content)->willReturn($mockTargetFile);

        $result = $this->storageService->writeFile($path, $fileName, $content);

        $this->assertInstanceOf(File::class, $result);
    }

    /**
     * Test part writing functionality
     *
     * This test verifies that the storage service can write
     * file parts correctly for chunked uploads.
     *
     * @covers ::writePart
     * @return void
     */
    public function testWritePartWithValidData(): void
    {
        $partId = 1;
        $partUuid = 'uuid-123';
        $data = 'test content';

        // Mock cache to return upload data
        $uploadData = [
            StorageService::UPLOAD_TARGET_ID => 123,
            StorageService::UPLOAD_TARGET_PATH => '/uploads/test-file.txt_parts',
            StorageService::NUMBER_OF_PARTS => 2
        ];
        $this->cache->method('get')->with("upload_$partUuid")->willReturn($uploadData);

        // Mock user folder
        $mockUserFolder = $this->createMock(Folder::class);
        $this->rootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        // Mock target file
        $mockTargetFile = $this->createMock(File::class);
        $mockTargetFile->method('getExtension')->willReturn('txt');
        $mockUserFolder->method('getFirstNodeById')->with(123)->willReturn($mockTargetFile);

        // Mock parts folder
        $mockPartsFolder = $this->createMock(Folder::class);
        $mockPartsFolder->method('getPath')->willReturn('/uploads/test-file.txt_parts');
        $this->rootFolder->method('get')->willReturn($mockPartsFolder);
        $mockPartsFolder->method('newFile')->willReturn($mockTargetFile);
        $mockPartsFolder->method('getDirectoryListing')->willReturn([]);

        $result = $this->storageService->writePart($partId, $partUuid, $data);

        $this->assertTrue($result);
    }

    /**
     * Test part writing with complete upload
     *
     * This test verifies that the storage service can handle
     * completing an upload when all parts are present.
     *
     * @covers ::writePart
     * @return void
     */
    public function testWritePartWithCompleteUpload(): void
    {
        $partId = 2;
        $partUuid = 'uuid-456';
        $data = 'test content part 2';

        // Mock cache to return upload data for a 2-part upload
        $uploadData = [
            StorageService::UPLOAD_TARGET_ID => 123,
            StorageService::UPLOAD_TARGET_PATH => '/uploads/test-file.txt_parts',
            StorageService::NUMBER_OF_PARTS => 2
        ];
        $this->cache->method('get')->with("upload_$partUuid")->willReturn($uploadData);

        // Mock user folder
        $mockUserFolder = $this->createMock(Folder::class);
        $this->rootFolder->method('getUserFolder')->willReturn($mockUserFolder);

        // Mock target file
        $mockTargetFile = $this->createMock(File::class);
        $mockTargetFile->method('getExtension')->willReturn('txt');
        $mockUserFolder->method('getFirstNodeById')->with(123)->willReturn($mockTargetFile);

        // Mock parts folder with existing parts
        $mockPartsFolder = $this->createMock(Folder::class);
        $mockPartsFolder->method('getPath')->willReturn('/uploads/test-file.txt_parts');
        $this->rootFolder->method('get')->willReturn($mockPartsFolder);
        $mockPartsFolder->method('newFile')->willReturn($mockTargetFile);
        
        // Mock existing part files
        $mockPartFile1 = $this->createMock(File::class);
        $mockPartFile1->method('getName')->willReturn('1.part.txt');
        $mockPartFile1->method('getContent')->willReturn('part 1 content');
        $mockPartFile1->method('delete')->willReturn(true);
        $mockPartFile1->method('getParent')->willReturn($mockPartsFolder);
        
        $mockPartFile2 = $this->createMock(File::class);
        $mockPartFile2->method('getName')->willReturn('2.part.txt');
        $mockPartFile2->method('getContent')->willReturn('part 2 content');
        $mockPartFile2->method('delete')->willReturn(true);
        $mockPartFile2->method('getParent')->willReturn($mockPartsFolder);
        
        $mockPartsFolder->method('getDirectoryListing')->willReturn([$mockPartFile1, $mockPartFile2]);
        $mockPartsFolder->method('getDirectoryListing')->willReturn([]); // After deletion

        $result = $this->storageService->writePart($partId, $partUuid, $data);

        $this->assertTrue($result);
    }
}
