<?php
/**
 * This file is part of the OpenConnector app.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service\EndpointHandler\Rule;

use OC\AppFramework\Http;
use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Exception\AuthenticationException;
use OCA\OpenConnector\Service\AuthorizationService;
use OCA\OpenConnector\Service\EndpointHandler\RuleHandlerInterface;
use OCP\AppFramework\Http\JSONResponse;
use Psr\Log\LoggerInterface;

/**
 * Handler for authentication rules.
 *
 * This class handles the processing of authentication rules for endpoints,
 * which verify if the request is properly authenticated.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
class AuthenticationRuleHandler implements RuleHandlerInterface
{
    /**
     * Constructor.
     *
     * @param AuthorizationService $authorizationService Service for handling authorization.
     * @param LoggerInterface      $logger               Logger for error logging.
     *
     * @return void
     */
    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * Determines if this handler can process the given rule.
     *
     * @param Rule $rule The rule to check.
     *
     * @return bool True if this handler can process the rule, false otherwise.
     */
    public function canProcess(Rule $rule): bool
    {
        return $rule->getType() === 'authentication';
    }//end canProcess()

    /**
     * Processes the given rule.
     *
     * @param Rule  $rule The rule to process.
     * @param array $data The data to process with the rule.
     *
     * @return array|JSONResponse The processed data, or a JSONResponse in case of an error.
     *
     * @psalm-param  array<string, mixed> $data
     * @psalm-return array<string, mixed>|JSONResponse
     */
    public function process(Rule $rule, array $data): array|JSONResponse
    {
        $configuration = $rule->getConfiguration();
        $header = $data['headers']['Authorization'] ?? $data['headers']['authorization'] ?? '';

        if ($header === '' || $header === null) {
            return new JSONResponse(
                ['error' => 'forbidden', 'details' => 'you are not allowed to access this endpoint unauthenticated'],
                Http::STATUS_FORBIDDEN
            );
        }

        if (isset($configuration['authentication']) === false) {
            return $data;
        }

        switch ($configuration['authentication']['type']) {
            case 'apikey':
                try {
                    $this->authorizationService->authorizeApiKey(
                        header: $header,
                        keys: $configuration['authentication']['keys']
                    );
                } catch (AuthenticationException $exception) {
                    return new JSONResponse(
                        data: ['error' => $exception->getMessage(), 'details' => $exception->getDetails()],
                        statusCode: 401
                    );
                }
                break;
            case 'jwt':
            case 'jwt-zgw':
                try {
                    $this->authorizationService->authorizeJwt(authorization: $header);
                } catch (AuthenticationException $exception) {
                    return new JSONResponse(
                        data: ['error' => $exception->getMessage(), 'details' => $exception->getDetails()],
                        statusCode: 401
                    );
                }
                break;
            case 'basic':
                try {
                    $this->authorizationService->authorizeBasic(
                        $header,
                        $configuration['authentication']['users'],
                        $configuration['authentication']['groups']
                    );
                } catch (AuthenticationException $exception) {
                    return new JSONResponse(
                        data: ['error' => $exception->getMessage(), 'details' => $exception->getDetails()],
                        statusCode: 401
                    );
                }
                break;
            case 'oauth':
                try {
                    $this->authorizationService->authorizeOAuth(
                        $header,
                        $configuration['authentication']['users'],
                        $configuration['authentication']['groups']
                    );
                } catch (AuthenticationException $exception) {
                    return new JSONResponse(
                        data: ['error' => $exception->getMessage(), 'details' => $exception->getDetails()],
                        statusCode: 401
                    );
                }
                break;
            default:
                return new JSONResponse(
                    data: ['error' => 'The authentication method is not supported'],
                    statusCode: Http::STATUS_NOT_IMPLEMENTED
                );
        }

        return $data;
    }//end process()
} 