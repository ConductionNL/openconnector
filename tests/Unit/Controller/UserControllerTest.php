<?php

declare(strict_types=1);

/**
 * UserControllerTest
 *
 * Unit tests for the UserController
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

use OCA\OpenConnector\Controller\UserController;
use OCA\OpenConnector\Service\AuthorizationService;
use OCA\OpenConnector\Service\UserService;
use OCA\OpenConnector\Service\OrganisationBridgeService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IUser;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the UserController
 *
 * This test class covers all functionality of the UserController
 * including authentication, user data retrieval, and user updates.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class UserControllerTest extends TestCase
{
    /**
     * The UserController instance being tested
     *
     * @var UserController
     */
    private UserController $controller;

    /**
     * Mock request object
     *
     * @var MockObject|IRequest
     */
    private MockObject $request;

    /**
     * Mock user manager
     *
     * @var MockObject|IUserManager
     */
    private MockObject $userManager;

    /**
     * Mock user session
     *
     * @var MockObject|IUserSession
     */
    private MockObject $userSession;

    /**
     * Mock authorization service
     *
     * @var MockObject|AuthorizationService
     */
    private MockObject $authorizationService;

    /**
     * Mock cache factory
     *
     * @var MockObject|ICacheFactory
     */
    private MockObject $cacheFactory;

    /**
     * Mock logger
     *
     * @var MockObject|LoggerInterface
     */
    private MockObject $logger;

    /**
     * Mock user service
     *
     * @var MockObject|UserService
     */
    private MockObject $userService;

    /**
     * Mock organisation bridge service
     *
     * @var MockObject|OrganisationBridgeService
     */
    private MockObject $organisationBridgeService;

    /**
     * Mock localization service
     *
     * @var MockObject|IL10N
     */
    private MockObject $l;

    /**
     * Mock user object
     *
     * @var MockObject|IUser
     */
    private MockObject $user;

    /**
     * Set up test environment before each test
     *
     * This method initializes all mocks and the controller instance
     * for testing purposes.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects for all dependencies
        $this->request = $this->createMock(IRequest::class);
        $this->userManager = $this->createMock(IUserManager::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->authorizationService = $this->createMock(AuthorizationService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->userService = $this->createMock(UserService::class);
        $this->organisationBridgeService = $this->createMock(OrganisationBridgeService::class);
        $this->l = $this->createMock(IL10N::class);
        $this->user = $this->createMock(IUser::class);

        // Configure IL10N mock to return the input string as-is (pass-through translation)
        $this->l->method('t')->willReturnArgument(0);

        // Configure cache factory to return a proper ICache mock
        $this->cacheFactory = $this->createMock(ICacheFactory::class);
        $cacheMock = $this->createMock(ICache::class);
        $this->cacheFactory->method('createDistributed')->willReturn($cacheMock);

        // Configure request to return a remote address for SecurityService
        $this->request->method('getRemoteAddress')->willReturn('127.0.0.1');

        // Initialize the controller with mocked dependencies
        $this->controller = new UserController(
            'openconnector',
            $this->request,
            $this->userManager,
            $this->userSession,
            $this->authorizationService,
            $this->cacheFactory,
            $this->logger,
            $this->userService,
            $this->organisationBridgeService,
            $this->l
        );
    }

    /**
     * Test successful retrieval of current user information
     *
     * This test verifies that the me() method returns correct user data
     * when a user is authenticated.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testMeSuccessful(): void
    {
        // Setup mock user data
        $this->setupMockUserData();

        // Mock user service to return the authenticated user
        $this->userService->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        // Mock user service to return built user data array
        $expectedUserData = [
            'uid' => 'testuser',
            'displayName' => 'Test User',
            'email' => 'test@example.com',
            'enabled' => true,
        ];
        $this->userService->expects($this->once())
            ->method('buildUserDataArray')
            ->with($this->user)
            ->willReturn($expectedUserData);

        // Execute the method
        $response = $this->controller->me();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(200, $response->getStatus());

        // Assert response contains expected user data
        $data = $response->getData();
        $this->assertEquals('testuser', $data['uid']);
        $this->assertEquals('Test User', $data['displayName']);
        $this->assertEquals('test@example.com', $data['email']);
        $this->assertTrue($data['enabled']);
    }

    /**
     * Test me() method when user is not authenticated
     *
     * This test verifies that the me() method returns proper error
     * when no user is logged in.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testMeUnauthenticated(): void
    {
        // Mock user service to return null (no authenticated user)
        $this->userService->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn(null);

        // Execute the method
        $response = $this->controller->me();

        // Assert response shows authentication error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(401, $response->getStatus());
        $this->assertEquals(['error' => 'User not authenticated'], $response->getData());
    }

    /**
     * Test successful user information update
     *
     * This test verifies that the updateMe() method correctly updates
     * user information when valid data is provided.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testUpdateMeSuccessful(): void
    {
        // Setup mock user data
        $this->setupMockUserData();

        // Mock user service to return the authenticated user
        $this->userService->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        // Mock request parameters with update data
        $updateData = [
            'displayName' => 'Updated User Name',
            'email' => 'updated@example.com',
            'language' => 'en'
        ];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock user service updateUserProperties
        $this->userService->expects($this->once())
            ->method('updateUserProperties')
            ->with($this->user, $updateData)
            ->willReturn([
                'success' => true,
                'message' => 'User properties updated successfully',
                'organisation_updated' => false
            ]);

        // Mock user service buildUserDataArray
        $this->userService->expects($this->once())
            ->method('buildUserDataArray')
            ->with($this->user)
            ->willReturn([
                'uid' => 'testuser',
                'displayName' => 'Updated User Name',
                'email' => 'updated@example.com',
            ]);

        // Execute the method
        $response = $this->controller->updateMe();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(200, $response->getStatus());
    }

    /**
     * Test updateMe() method when user is not authenticated
     *
     * This test verifies that the updateMe() method returns proper error
     * when no user is logged in.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testUpdateMeUnauthenticated(): void
    {
        // Mock user service to return null (no authenticated user)
        $this->userService->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn(null);

        // Execute the method
        $response = $this->controller->updateMe();

        // Assert response shows authentication error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(401, $response->getStatus());
        $this->assertEquals(['error' => 'User not authenticated'], $response->getData());
    }

    /**
     * Test successful user login
     *
     * This test verifies that the login() method successfully authenticates
     * a user with valid credentials.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testLoginSuccessful(): void
    {
        // Setup mock user data
        $this->setupMockUserData();

        // Mock request parameters with login credentials
        $loginData = [
            'username' => 'testuser',
            'password' => 'testpassword'
        ];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($loginData);

        // Mock user manager to return authenticated user
        $this->userManager->expects($this->once())
            ->method('checkPassword')
            ->with('testuser', 'testpassword')
            ->willReturn($this->user);

        // Mock user session to set the authenticated user
        $this->userSession->expects($this->once())
            ->method('setUser')
            ->with($this->user);

        // Mock user service to return built user data array
        $expectedUserData = [
            'uid' => 'testuser',
            'displayName' => 'Test User',
            'email' => 'test@example.com',
        ];
        $this->userService->expects($this->once())
            ->method('buildUserDataArray')
            ->with($this->user)
            ->willReturn($expectedUserData);

        // Execute the method
        $response = $this->controller->login();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(200, $response->getStatus());

        // Assert response contains success message and user data
        $data = $response->getData();
        $this->assertEquals('Login successful', $data['message']);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('testuser', $data['user']['uid']);
    }

    /**
     * Test login with invalid credentials
     *
     * This test verifies that the login() method returns proper error
     * when invalid credentials are provided.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testLoginInvalidCredentials(): void
    {
        // Mock request parameters with invalid credentials
        $loginData = [
            'username' => 'testuser',
            'password' => 'wrongpassword'
        ];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($loginData);

        // Mock user manager to return false for invalid credentials
        $this->userManager->expects($this->once())
            ->method('checkPassword')
            ->with('testuser', 'wrongpassword')
            ->willReturn(false);

        // Execute the method
        $response = $this->controller->login();

        // Assert response shows authentication error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(401, $response->getStatus());
        $this->assertEquals(['error' => 'Invalid username or password'], $response->getData());
    }

    /**
     * Test login with missing credentials
     *
     * This test verifies that the login() method returns proper error
     * when required credentials are missing.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testLoginMissingCredentials(): void
    {
        // Mock request parameters with missing credentials
        $loginData = [
            'username' => 'testuser'
            // password is missing
        ];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($loginData);

        // Execute the method
        $response = $this->controller->login();

        // Assert response shows validation error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals(['error' => 'Username and password are required'], $response->getData());
    }

    /**
     * Test login with empty credentials
     *
     * This test verifies that the login() method returns proper error
     * when credentials are empty strings.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testLoginEmptyCredentials(): void
    {
        // Mock request parameters with empty credentials
        $loginData = [
            'username' => '',
            'password' => ''
        ];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($loginData);

        // Execute the method
        $response = $this->controller->login();

        // Assert response shows validation error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(400, $response->getStatus());
        $this->assertEquals(['error' => 'Username and password are required'], $response->getData());
    }

    /**
     * Test exception handling in me() method
     *
     * This test verifies that the me() method properly handles exceptions
     * and returns appropriate error responses.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testMeException(): void
    {
        // Mock user service to throw exception
        $this->userService->expects($this->once())
            ->method('getCurrentUser')
            ->willThrowException(new \Exception('Test exception'));

        // Execute the method
        $response = $this->controller->me();

        // Assert response shows error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(500, $response->getStatus());
        $this->assertStringContainsString('Failed to retrieve user information', $response->getData()['error']);
    }

    /**
     * Test exception handling in updateMe() method
     *
     * This test verifies that the updateMe() method properly handles exceptions
     * and returns appropriate error responses.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testUpdateMeException(): void
    {
        // Mock user service to throw exception
        $this->userService->expects($this->once())
            ->method('getCurrentUser')
            ->willThrowException(new \Exception('Test exception'));

        // Execute the method
        $response = $this->controller->updateMe();

        // Assert response shows error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(500, $response->getStatus());
        $this->assertStringContainsString('Failed to update user information', $response->getData()['error']);
    }

    /**
     * Test exception handling in login() method
     *
     * This test verifies that the login() method properly handles exceptions
     * and returns appropriate error responses.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    public function testLoginException(): void
    {
        // Mock request parameters
        $loginData = [
            'username' => 'testuser',
            'password' => 'testpassword'
        ];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($loginData);

        // Mock user manager to throw exception
        $this->userManager->expects($this->once())
            ->method('checkPassword')
            ->willThrowException(new \Exception('Test exception'));

        // Execute the method
        $response = $this->controller->login();

        // Assert response shows error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(500, $response->getStatus());
        $this->assertStringContainsString('Login failed', $response->getData()['error']);
    }

    /**
     * Set up mock user data for testing
     *
     * This helper method configures the mock user object with realistic
     * test data for use in various test scenarios.
     *
     * @return void
     *
     * @psalm-return void
     * @phpstan-return void
     */
    private function setupMockUserData(): void
    {
        // Configure mock user with test data (only methods from IUser interface)
        $this->user->method('getUID')->willReturn('testuser');
        $this->user->method('getDisplayName')->willReturn('Test User');
        $this->user->method('getEMailAddress')->willReturn('test@example.com');
        $this->user->method('isEnabled')->willReturn(true);
        $this->user->method('getQuota')->willReturn('1 GB');
        $this->user->method('getLastLogin')->willReturn(1640995200);
        $this->user->method('getBackendClassName')->willReturn('Database');

        // Configure capability methods (only those on the IUser interface)
        $this->user->method('canChangeDisplayName')->willReturn(true);
        $this->user->method('canChangePassword')->willReturn(true);
        $this->user->method('canChangeAvatar')->willReturn(true);
    }
}
