<?php

declare(strict_types=1);

/**
 * UserControllerTest
 * 
 * Unit tests for the UserController with updated dependencies and CORS support
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
use OCA\OpenConnector\Service\SecurityService;
use OCA\OpenConnector\Service\UserService;
use OCA\OpenConnector\Service\OrganisationBridgeService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IUser;
use OCP\ICacheFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the UserController
 *
 * This test class covers all functionality of the UserController
 * including authentication, user data retrieval, user updates, and CORS handling.
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
     * Mock user object
     *
     * @var MockObject|IUser
     */
    private MockObject $user;

    /**
     * Mock admin user object
     *
     * @var MockObject|IUser
     */
    private MockObject $adminUser;

    /**
     * Set up test environment before each test
     *
     * This method initializes all mocks and the controller instance
     * for testing purposes with updated dependencies.
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
        $this->cacheFactory = $this->createMock(ICacheFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->userService = $this->createMock(UserService::class);
        $this->organisationBridgeService = $this->createMock(OrganisationBridgeService::class);
        $this->user = $this->createMock(IUser::class);
        $this->adminUser = $this->createMock(IUser::class);

        // Setup request headers for CORS testing
        $this->request->method('getHeader')
            ->willReturnMap([
                ['Origin', 'https://localhost:3000']
            ]);
        $this->request->method('server')
            ->willReturn(['HTTP_ORIGIN' => 'https://localhost:3000']);

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
            $this->organisationBridgeService
        );
    }

    /**
     * Test CORS preflight response
     *
     * This test verifies that the preflightedCors() method returns proper
     * CORS headers for OPTIONS requests.
     *
     * @return void
     * 
     * @psalm-return void
     * @phpstan-return void
     */
    public function testPreflightedCors(): void
    {
        // Execute the method
        $response = $this->controller->preflightedCors();

        // Assert response has correct CORS headers
        $headers = $response->getHeaders();
        $this->assertEquals('https://localhost:3000', $headers['Access-Control-Allow-Origin']);
        $this->assertEquals('PUT, POST, GET, DELETE, PATCH', $headers['Access-Control-Allow-Methods']);
        $this->assertEquals('Authorization, Content-Type, Accept', $headers['Access-Control-Allow-Headers']);
        $this->assertEquals('1728000', $headers['Access-Control-Max-Age']);
        $this->assertEquals('false', $headers['Access-Control-Allow-Credentials']);
    }

    /**
     * Test successful admin login
     *
     * This test verifies that the login() method successfully authenticates
     * an admin user with valid credentials and includes CORS headers.
     *
     * @return void
     * 
     * @psalm-return void
     * @phpstan-return void
     */
    public function testAdminLoginSuccessful(): void
    {
        // Setup mock admin user data
        $this->setupMockAdminUserData();

        // Mock request parameters with admin login credentials
        $loginData = [
            'username' => 'admin',
            'password' => 'admin123'
        ];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($loginData);

        // Mock security service validation (should be successful)
        $this->mockSecurityServiceForSuccessfulLogin();

        // Mock user manager to return authenticated admin user
        $this->userManager->expects($this->once())
            ->method('checkPassword')
            ->with('admin', 'admin123')
            ->willReturn($this->adminUser);

        // Mock user session to set the authenticated admin user
        $this->userSession->expects($this->once())
            ->method('setUser')
            ->with($this->adminUser);

        // Mock user service to build admin user data
        $this->userService->expects($this->once())
            ->method('buildUserDataArray')
            ->with($this->adminUser)
            ->willReturn([
                'uid' => 'admin',
                'displayName' => 'Administrator',
                'email' => 'admin@example.com',
                'enabled' => true,
                'isAdmin' => true,
                'groups' => ['admin'],
                'permissions' => ['all']
            ]);

        // Execute the method
        $response = $this->controller->login();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(200, $response->getStatus());

        // Assert response contains success message and admin user data
        $data = $response->getData();
        $this->assertEquals('Login successful', $data['message']);
        $this->assertArrayHasKey('user', $data);
        $this->assertEquals('admin', $data['user']['uid']);
        $this->assertTrue($data['user']['isAdmin']);
        $this->assertTrue($data['session_created']);

        // Assert CORS headers are present
        $headers = $response->getHeaders();
        $this->assertEquals('https://localhost:3000', $headers['Access-Control-Allow-Origin']);
    }

    /**
     * Test successful regular user login
     *
     * This test verifies that the login() method successfully authenticates
     * a regular user with valid credentials.
     *
     * @return void
     * 
     * @psalm-return void
     * @phpstan-return void
     */
    public function testUserLoginSuccessful(): void
    {
        // Setup mock user data
        $this->setupMockUserData();

        // Mock request parameters with user login credentials
        $loginData = [
            'username' => 'testuser',
            'password' => 'testpassword'
        ];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($loginData);

        // Mock security service validation (should be successful)
        $this->mockSecurityServiceForSuccessfulLogin();

        // Mock user manager to return authenticated user
        $this->userManager->expects($this->once())
            ->method('checkPassword')
            ->with('testuser', 'testpassword')
            ->willReturn($this->user);

        // Mock user session to set the authenticated user
        $this->userSession->expects($this->once())
            ->method('setUser')
            ->with($this->user);

        // Mock user service to build user data
        $this->userService->expects($this->once())
            ->method('buildUserDataArray')
            ->with($this->user)
            ->willReturn([
                'uid' => 'testuser',
                'displayName' => 'Test User',
                'email' => 'test@example.com',
                'enabled' => true
            ]);

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
     * Test successful retrieval of current user information with CORS
     *
     * This test verifies that the me() method returns correct user data
     * when a user is authenticated and includes CORS headers.
     *
     * @return void
     * 
     * @psalm-return void
     * @phpstan-return void
     */
    public function testMeSuccessfulWithCors(): void
    {
        // Setup mock user data
        $this->setupMockUserData();

        // Mock user service to return current user
        $this->userService->expects($this->once())
            ->method('getCurrentUser')
            ->willReturn($this->user);

        // Mock user service to build user data
        $this->userService->expects($this->once())
            ->method('buildUserDataArray')
            ->with($this->user)
            ->willReturn([
                'uid' => 'testuser',
                'displayName' => 'Test User',
                'email' => 'test@example.com',
                'enabled' => true
            ]);

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

        // Assert CORS headers are present
        $headers = $response->getHeaders();
        $this->assertEquals('https://localhost:3000', $headers['Access-Control-Allow-Origin']);
    }

    /**
     * Test me() method when user is not authenticated
     *
     * This test verifies that the me() method returns proper error
     * when no user is logged in and includes CORS headers.
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

        // Assert CORS headers are present
        $headers = $response->getHeaders();
        $this->assertEquals('https://localhost:3000', $headers['Access-Control-Allow-Origin']);
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

        // Mock user session to return the authenticated user
        $this->userSession->expects($this->once())
            ->method('getUser')
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

        // Mock user update methods
        $this->user->expects($this->once())
            ->method('canChangeDisplayName')
            ->willReturn(true);
        $this->user->expects($this->once())
            ->method('setDisplayName')
            ->with('Updated User Name');

        $this->user->expects($this->once())
            ->method('canChangeMailAddress')
            ->willReturn(true);
        $this->user->expects($this->once())
            ->method('setEMailAddress')
            ->with('updated@example.com');

        $this->user->expects($this->once())
            ->method('setLanguage')
            ->with('en');

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
        // Mock user session to return null (no authenticated user)
        $this->userSession->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        // Execute the method
        $response = $this->controller->updateMe();

        // Assert response shows authentication error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(401, $response->getStatus());
        $this->assertEquals(['error' => 'User not authenticated'], $response->getData());
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
        // Mock user session to throw exception
        $this->userSession->expects($this->once())
            ->method('getUser')
            ->willThrowException(new \Exception('Test exception'));

        // Execute the method
        $response = $this->controller->me();

        // Assert response shows error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(500, $response->getStatus());
        $this->assertStringContains('Failed to retrieve user information', $response->getData()['error']);
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
        // Mock user session to throw exception
        $this->userSession->expects($this->once())
            ->method('getUser')
            ->willThrowException(new \Exception('Test exception'));

        // Execute the method
        $response = $this->controller->updateMe();

        // Assert response shows error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(500, $response->getStatus());
        $this->assertStringContains('Failed to update user information', $response->getData()['error']);
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
        $this->assertStringContains('Login failed', $response->getData()['error']);
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
        // Configure mock user with test data
        $this->user->method('getUID')->willReturn('testuser');
        $this->user->method('getDisplayName')->willReturn('Test User');
        $this->user->method('getEMailAddress')->willReturn('test@example.com');
        $this->user->method('isEnabled')->willReturn(true);
        $this->user->method('getLastLogin')->willReturn(1640995200);
    }

    /**
     * Set up mock admin user data for testing
     *
     * This helper method configures the mock admin user object with
     * administrative privileges for testing admin-specific scenarios.
     *
     * @return void
     * 
     * @psalm-return void
     * @phpstan-return void
     */
    private function setupMockAdminUserData(): void
    {
        // Configure mock admin user with test data
        $this->adminUser->method('getUID')->willReturn('admin');
        $this->adminUser->method('getDisplayName')->willReturn('Administrator');
        $this->adminUser->method('getEMailAddress')->willReturn('admin@example.com');
        $this->adminUser->method('isEnabled')->willReturn(true);
        $this->adminUser->method('getLastLogin')->willReturn(1640995200);
    }

    /**
     * Mock security service for successful login scenario
     *
     * This helper method sets up the security service mocks for
     * a successful login flow without rate limiting issues.
     *
     * @return void
     * 
     * @psalm-return void
     * @phpstan-return void
     */
    private function mockSecurityServiceForSuccessfulLogin(): void
    {
        // Since SecurityService is instantiated in constructor, we need to mock
        // the behavior indirectly through the controller's methods
        // For now, we'll assume the security service allows the login
        // In a real implementation, you might need dependency injection for SecurityService
    }
} 