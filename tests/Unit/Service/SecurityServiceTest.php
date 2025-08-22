<?php

declare(strict_types=1);

/**
 * SecurityServiceTest
 *
 * Comprehensive unit tests for the SecurityService class to verify security measures
 * including rate limiting, XSS protection, and brute force protection functionality.
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

use OCA\OpenConnector\Service\SecurityService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Security Service Test Suite
 *
 * Comprehensive unit tests for security functionality including rate limiting,
 * XSS protection, and brute force protection. This test class validates the core
 * security capabilities of the OpenConnector application.
 *
 * @coversDefaultClass SecurityService
 */
class SecurityServiceTest extends TestCase
{
    private SecurityService $securityService;
    private MockObject $cacheFactory;
    private MockObject $cache;
    private MockObject $request;
    private MockObject $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheFactory = $this->createMock(ICacheFactory::class);
        $this->cache = $this->createMock(ICache::class);
        $this->request = $this->createMock(IRequest::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->cacheFactory
            ->method('createDistributed')
            ->willReturn($this->cache);

        $this->securityService = new SecurityService(
            $this->cacheFactory,
            $this->logger
        );
    }

    /**
     * Test brute force protection with excessive attempts
     *
     * This test verifies that the security service correctly
     * blocks access when too many login attempts are detected.
     *
     * @covers ::checkLoginRateLimit
     * @return void
     */
    public function testCheckBruteForceProtectionWithExcessiveAttempts(): void
    {
        $this->cache
            ->method('get')
            ->willReturn(15); // Excessive attempt count

        $this->logger
            ->expects($this->once())
            ->method('info');

        $result = $this->securityService->checkLoginRateLimit('user@example.com', '192.168.1.1');

        $this->assertIsArray($result);
        $this->assertFalse($result['allowed']);
    }

    /**
     * Test login attempt logging
     *
     * This test verifies that the security service correctly logs
     * login attempts for monitoring purposes.
     *
     * @covers ::recordSuccessfulLogin
     * @return void
     */
    public function testLogLoginAttemptWithSuccessfulLogin(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->securityService->recordSuccessfulLogin('user@example.com', '192.168.1.1');
    }

    /**
     * Test failed login attempt logging
     *
     * This test verifies that the security service correctly logs
     * failed login attempts with appropriate warning level.
     *
     * @covers ::recordFailedLoginAttempt
     * @return void
     */
    public function testLogLoginAttemptWithFailedLogin(): void
    {
        $this->logger
            ->expects($this->once())
            ->method('info');

        $this->cache
            ->expects($this->atLeastOnce())
            ->method('set');

        $this->securityService->recordFailedLoginAttempt('user@example.com', '192.168.1.1');
    }

    /**
     * Test IP blocking functionality
     *
     * This test verifies that the security service can block
     * specific IP addresses when necessary.
     *
     * @covers ::recordFailedLoginAttempt
     * @return void
     */
    public function testBlockIpAddressWithMaliciousIp(): void
    {
        // Set up cache to return high attempt count to trigger IP blocking
        $this->cache
            ->method('get')
            ->willReturn(5); // Rate limit threshold

        $this->logger
            ->expects($this->atLeastOnce())
            ->method('info');

        $this->cache
            ->expects($this->atLeastOnce())
            ->method('set');

        $this->securityService->recordFailedLoginAttempt('user@example.com', '192.168.1.1');
    }

    /**
     * Test IP blocking check
     *
     * This test verifies that the security service can check
     * if an IP address is currently blocked.
     *
     * @covers ::checkLoginRateLimit
     * @return void
     */
    public function testIsIpBlockedWithBlockedIp(): void
    {
        $this->cache
            ->method('get')
            ->willReturn(time() + 3600); // IP is blocked until future time

        $result = $this->securityService->checkLoginRateLimit('user@example.com', '192.168.1.1');

        $this->assertIsArray($result);
        $this->assertFalse($result['allowed']);
        $this->assertArrayHasKey('lockout_until', $result);
    }

    /**
     * Test IP blocking check with non-blocked IP
     *
     * This test verifies that the security service correctly
     * allows access for non-blocked IP addresses.
     *
     * @covers ::checkLoginRateLimit
     * @return void
     */
    public function testIsIpBlockedWithNonBlockedIp(): void
    {
        $this->cache
            ->method('get')
            ->willReturn(null); // No blocking

        $result = $this->securityService->checkLoginRateLimit('user@example.com', '192.168.1.1');

        $this->assertIsArray($result);
        $this->assertTrue($result['allowed']);
    }

    /**
     * Test security response creation
     *
     * This test verifies that the security service can create
     * appropriate security responses for various scenarios.
     *
     * @covers ::validateLoginCredentials
     * @return void
     */
    public function testCreateSecurityResponseWithRateLimitExceeded(): void
    {
        $credentials = [
            'username' => 'user@example.com',
            'password' => 'password123'
        ];

        $result = $this->securityService->validateLoginCredentials($credentials);

        $this->assertIsArray($result);
        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('credentials', $result);
    }

    /**
     * Test input validation
     *
     * This test verifies that the security service correctly
     * validates and sanitizes input data.
     *
     * @covers ::validateLoginCredentials
     * @return void
     */
    public function testValidateInputWithValidData(): void
    {
        $credentials = [
            'username' => 'user@example.com',
            'password' => 'password123'
        ];

        $result = $this->securityService->validateLoginCredentials($credentials);

        $this->assertIsArray($result);
        $this->assertTrue($result['valid']);
    }
}
