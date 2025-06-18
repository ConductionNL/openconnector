<?php

declare(strict_types=1);

/**
 * UserController
 *
 * This controller handles user-related API endpoints including user information
 * retrieval, updates, and authentication with comprehensive security measures.
 *
 * @category  Controller
 * @package   OpenConnector
 * @author    Conduction <info@conduction.nl>
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   1.0.0
 * @link      https://github.com/ConductionNL/opencatalogi
 */

namespace OCA\OpenConnector\Controller;

use OCA\OpenConnector\Service\AuthorizationService;
use OCA\OpenConnector\Service\SecurityService;
use OCA\OpenConnector\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ICacheFactory;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use Psr\Log\LoggerInterface;

/**
 * Controller class for handling user-related API endpoints
 *
 * This controller provides secure API endpoints for user management operations
 * including authentication, profile retrieval, and updates with comprehensive
 * security measures against XSS and brute force attacks.
 *
 * @psalm-suppress UnusedClass
 */
class UserController extends Controller
{
    /**
     * User manager for user operations
     *
     * @var IUserManager
     */
    private readonly IUserManager $userManager;

    /**
     * User session manager for session operations
     *
     * @var IUserSession
     */
    private readonly IUserSession $userSession;

    /**
     * Authorization service for handling authentication
     *
     * @var AuthorizationService
     */
    private readonly AuthorizationService $authorizationService;

    /**
     * Security service for handling rate limiting and XSS protection
     *
     * @var SecurityService
     */
    private readonly SecurityService $securityService;

    /**
     * User service for user-related business logic
     *
     * @var UserService
     */
    private readonly UserService $userService;

    /**
     * Constructor for the UserController
     *
     * Initializes the controller with required dependencies for user management
     * and authentication operations.
     *
     * @param string $appName The name of the app
     * @param IRequest $request The request object for handling HTTP requests
     * @param IUserManager $userManager The user manager for user operations
     * @param IUserSession $userSession The user session manager
     * @param AuthorizationService $authorizationService The authorization service
     * @param ICacheFactory $cacheFactory The cache factory for rate limiting
     * @param LoggerInterface $logger The logger for security events
     * @param UserService $userService The user service for user-related operations
     *
     * @psalm-param string $appName
     * @psalm-param IRequest $request
     * @psalm-param IUserManager $userManager
     * @psalm-param IUserSession $userSession
     * @psalm-param AuthorizationService $authorizationService
     * @psalm-param ICacheFactory $cacheFactory
     * @psalm-param LoggerInterface $logger
     * @psalm-param UserService $userService
     * @phpstan-param string $appName
     * @phpstan-param IRequest $request
     * @phpstan-param IUserManager $userManager
     * @phpstan-param IUserSession $userSession
     * @phpstan-param AuthorizationService $authorizationService
     * @phpstan-param ICacheFactory $cacheFactory
     * @phpstan-param LoggerInterface $logger
     * @phpstan-param UserService $userService
     */
    public function __construct(
        string $appName,
        IRequest $request,
        IUserManager $userManager,
        IUserSession $userSession,
        AuthorizationService $authorizationService,
        ICacheFactory $cacheFactory,
        LoggerInterface $logger,
        UserService $userService
    ) {
        parent::__construct($appName, $request);
        $this->userManager = $userManager;
        $this->userSession = $userSession;
        $this->authorizationService = $authorizationService;
        $this->securityService = new SecurityService($cacheFactory, $logger);
        $this->userService = $userService;
    }

    /**
     * Get current user information as JSON object
     *
     * This method returns the current authenticated user's information
     * in JSON format for external API consumption with security headers.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the current user's information
     * 
     * @psalm-return JSONResponse
     * @phpstan-return JSONResponse
     */
    public function me(): JSONResponse
    {
        try {
            // Get the current user from the session
            $currentUser = $this->userService->getCurrentUser();
            
            // Check if user is logged in
            if ($currentUser === null) {
                $response = new JSONResponse(
                    data: ['error' => 'User not authenticated'],
                    statusCode: 401
                );
                return $this->securityService->addSecurityHeaders($response);
            }

            // Build user data array with essential information (already sanitized)
            $userData = $this->userService->buildUserDataArray($currentUser);

            $response = new JSONResponse($userData);
            return $this->securityService->addSecurityHeaders($response);
        } catch (\Exception $e) {
            // Log the error and return generic error response
            $response = new JSONResponse(
                data: ['error' => 'Failed to retrieve user information'],
                statusCode: 500
            );
            return $this->securityService->addSecurityHeaders($response);
        }
    }

    /**
     * Update current user information from JSON object
     *
     * This method securely updates the current authenticated user's information
     * based on the provided JSON data with input sanitization and validation.
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse A JSON response containing the updated user information
     * 
     * @psalm-return JSONResponse
     * @phpstan-return JSONResponse
     */
    public function updateMe(): JSONResponse
    {
        try {
            // Get the current user from the session
            $currentUser = $this->userService->getCurrentUser();
            
            // Check if user is logged in
            if ($currentUser === null) {
                $response = new JSONResponse(
                    data: ['error' => 'User not authenticated'],
                    statusCode: 401
                );
                return $this->securityService->addSecurityHeaders($response);
            }

            // Get and sanitize the request data to prevent XSS
            $data = $this->request->getParams();
            $sanitizedData = $this->securityService->sanitizeInput($data);
            
            // Remove system parameters that shouldn't be updated
            foreach ($sanitizedData as $key => $value) {
                if (str_starts_with($key, '_') === true) {
                    unset($sanitizedData[$key]);
                }
            }

            // Update user properties based on provided data
            $this->userService->updateUserProperties($currentUser, $sanitizedData);

            // Build updated user data array
            $userData = $this->userService->buildUserDataArray($currentUser);

            $response = new JSONResponse($userData);
            return $this->securityService->addSecurityHeaders($response);
        } catch (\Exception $e) {
            // Log the error and return generic error response
            $response = new JSONResponse(
                data: ['error' => 'Failed to update user information'],
                statusCode: 500
            );
            return $this->securityService->addSecurityHeaders($response);
        }
    }

    /**
     * Login a user based on username and password combination
     *
     * This method securely authenticates a user using their username/email and password,
     * with comprehensive protection against XSS and brute force attacks including:
     * - Input validation and sanitization
     * - Rate limiting per user and IP
     * - Progressive delays for repeated attempts
     * - Account and IP lockout mechanisms
     * - Security event logging
     * - Security headers in response
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * @PublicPage
     *
     * @return JSONResponse A JSON response containing login result and user information
     * 
     * @psalm-return JSONResponse
     * @phpstan-return JSONResponse
     */
    public function login(): JSONResponse
    {
        try {
            // Get client IP address for rate limiting
            $clientIp = $this->securityService->getClientIpAddress($this->request);
            
            // Get and sanitize login credentials from request
            $data = $this->request->getParams();
            
            // Validate and sanitize credentials to prevent XSS attacks
            $credentialValidation = $this->securityService->validateLoginCredentials($data);
            if ($credentialValidation['valid'] === false) {
                $response = new JSONResponse(
                    data: ['error' => $credentialValidation['error']],
                    statusCode: 400
                );
                return $this->securityService->addSecurityHeaders($response);
            }

            $credentials = $credentialValidation['credentials'];
            $username = $credentials['username'];
            $password = $credentials['password'];

            // Check rate limiting before attempting authentication
            $rateLimitCheck = $this->securityService->checkLoginRateLimit($username, $clientIp);
            if ($rateLimitCheck['allowed'] === false) {
                // Apply progressive delay if specified
                if (isset($rateLimitCheck['delay']) === true) {
                    sleep($rateLimitCheck['delay']);
                }
                
                $response = new JSONResponse(
                    data: [
                        'error' => $rateLimitCheck['reason'],
                        'retry_after' => $rateLimitCheck['delay'] ?? null,
                        'lockout_until' => $rateLimitCheck['lockout_until'] ?? null
                    ],
                    statusCode: 429 // Too Many Requests
                );
                return $this->securityService->addSecurityHeaders($response);
            }

            // Attempt to authenticate the user
            $user = $this->userManager->checkPassword($username, $password);

            // Check if authentication was successful
            if ($user === false) {
                // Record failed login attempt for rate limiting
                $this->securityService->recordFailedLoginAttempt($username, $clientIp, 'invalid_credentials');
                
                // Return generic error message to prevent username enumeration
                $response = new JSONResponse(
                    data: ['error' => 'Invalid username or password'],
                    statusCode: 401
                );
                return $this->securityService->addSecurityHeaders($response);
            }

            // Check if user account is enabled
            if ($user->isEnabled() === false) {
                // Record failed login attempt for disabled account
                $this->securityService->recordFailedLoginAttempt($username, $clientIp, 'account_disabled');
                
                $response = new JSONResponse(
                    data: ['error' => 'Account is disabled'],
                    statusCode: 401
                );
                return $this->securityService->addSecurityHeaders($response);
            }

            // Authentication successful - record success and clear rate limits
            $this->securityService->recordSuccessfulLogin($username, $clientIp);

            // Set the user in the session to create login session
            $this->userSession->setUser($user);

            // Build user data array for response (sanitized)
            $userData = $this->userService->buildUserDataArray($user);

            // Create successful response with security headers
            $response = new JSONResponse([
                'message' => 'Login successful',
                'user' => $userData,
                'session_created' => true
            ]);
            
            return $this->securityService->addSecurityHeaders($response);
        } catch (\Exception $e) {
            // Log the error securely without exposing sensitive information
            $response = new JSONResponse(
                data: ['error' => 'Login failed due to a system error'],
                statusCode: 500
            );
            return $this->securityService->addSecurityHeaders($response);
        }
    }
} 