<?php

declare(strict_types=1);

/**
 * UserServiceTest
 *
 * Comprehensive unit tests for the UserService class to verify user data retrieval,
 * profile management, group operations, and account property handling.
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

use Exception;
use OCA\OpenConnector\Service\OrganisationBridgeService;
use OCA\OpenConnector\Service\UserService;
use OCP\Accounts\IAccountManager;
use OCP\Accounts\IAccountProperty;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\IConfig;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * User Service Test Suite
 *
 * Comprehensive unit tests for user management operations, profile data
 * building, group memberships, and account property handling. This test
 * class validates the core functionality of user data retrieval and
 * management within the NextCloud environment.
 *
 * @coversDefaultClass UserService
 */
class UserServiceTest extends TestCase
{
    /**
     * The UserService instance being tested
     *
     * @var UserService
     */
    private UserService $userService;

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
     * Mock config service
     *
     * @var MockObject|IConfig
     */
    private MockObject $config;

    /**
     * Mock group manager
     *
     * @var MockObject|IGroupManager
     */
    private MockObject $groupManager;

    /**
     * Mock account manager
     *
     * @var MockObject|IAccountManager
     */
    private MockObject $accountManager;

    /**
     * Mock logger
     *
     * @var MockObject|LoggerInterface
     */
    private MockObject $logger;

    /**
     * Mock organisation bridge service
     *
     * @var MockObject|OrganisationBridgeService
     */
    private MockObject $organisationBridgeService;

    /**
     * Set up test environment before each test
     *
     * This method initializes the UserService with mocked dependencies
     * for testing purposes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects
        $this->userManager = $this->createMock(IUserManager::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->config = $this->createMock(IConfig::class);
        $this->groupManager = $this->createMock(IGroupManager::class);
        $this->accountManager = $this->createMock(IAccountManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->organisationBridgeService = $this->createMock(OrganisationBridgeService::class);

        // Create the service
        $this->userService = new UserService(
            $this->userManager,
            $this->userSession,
            $this->config,
            $this->groupManager,
            $this->accountManager,
            $this->logger,
            $this->organisationBridgeService
        );
    }

    /**
     * Test getCurrentUser method with authenticated user
     *
     * This test verifies that the getCurrentUser method correctly
     * returns the currently authenticated user.
     *
     * @covers ::getCurrentUser
     * @return void
     */
    public function testGetCurrentUserWithAuthenticatedUser(): void
    {
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('testuser');

        $this->userSession
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $result = $this->userService->getCurrentUser();

        $this->assertSame($user, $result);
    }

    /**
     * Test getCurrentUser method with no authenticated user
     *
     * This test verifies that the getCurrentUser method correctly
     * returns null when no user is authenticated.
     *
     * @covers ::getCurrentUser
     * @return void
     */
    public function testGetCurrentUserWithNoAuthenticatedUser(): void
    {
        $this->userSession
            ->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $result = $this->userService->getCurrentUser();

        $this->assertNull($result);
    }

    /**
     * Test buildUserDataArray method with minimal user data
     *
     * This test verifies that the buildUserDataArray method correctly
     * handles users with minimal data.
     *
     * @covers ::buildUserDataArray
     * @return void
     */
    public function testBuildUserDataArrayWithMinimalUserData(): void
    {
        $user = $this->createMock(IUser::class);
        $user->method('getUID')->willReturn('testuser');
        $user->method('getDisplayName')->willReturn('Test User');
        $user->method('getEMailAddress')->willReturn(null);
        $user->method('getLastLogin')->willReturn(0);
        $user->method('getHome')->willReturn('/home/testuser');
        $user->method('getBackendClassName')->willReturn('Database');

        $this->groupManager
            ->method('getUserGroups')
            ->with($user)
            ->willReturn([]);

        $this->config
            ->method('getUserValue')
            ->willReturnMap([
                ['testuser', 'core', 'quota', '', ''],
                ['testuser', 'core', 'enabled', 'yes', 'yes']
            ]);

        $this->accountManager
            ->method('getAccount')
            ->with($user)
            ->willReturn($this->createMock(\OCP\Accounts\IAccount::class));

        $result = $this->userService->buildUserDataArray($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('uid', $result);
        $this->assertArrayHasKey('displayName', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('groups', $result);
        $this->assertEquals('testuser', $result['uid']);
        $this->assertEquals('Test User', $result['displayName']);
        $this->assertNull($result['email']);
        $this->assertEmpty($result['groups']);
    }
}
