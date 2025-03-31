<?php
/**
 * Service for processing rules.
 *
 * This service manages the processing of different types of rules for endpoints,
 * delegating to appropriate handlers based on rule type.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version   GIT: 1.0.0
 * @link      https://OpenConnector.app
 */

namespace OCA\OpenConnector\Service;

use Exception;
use JWadhams\JsonLogic;
use OCA\OpenConnector\Db\Endpoint;
use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Db\RuleMapper;
use OCA\OpenConnector\Service\Handler\RuleHandlerInterface;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * Service for processing rules.
 */
class RuleProcessorService
{
    /**
     * The registered rule handlers.
     *
     * @var RuleHandlerInterface[]
     */
    private array $handlers = [];

    /**
     * Constructor.
     *
     * @param RuleMapper      $ruleMapper Rule mapper for fetching rules.
     * @param LoggerInterface $logger     Logger for error logging.
     *
     * @return void
     */
    public function __construct(
        private readonly RuleMapper $ruleMapper,
        private readonly LoggerInterface $logger
    ) {
    }//end __construct()

    /**
     * Register a rule handler.
     *
     * @param RuleHandlerInterface $handler The rule handler to register.
     *
     * @return void
     */
    public function registerHandler(RuleHandlerInterface $handler): void
    {
        $this->handlers[] = $handler;
    }//end registerHandler()

    /**
     * Get a rule by its ID.
     *
     * @param string $id The unique identifier of the rule.
     *
     * @return Rule|null The rule object if found, or null if not found.
     */
    private function getRuleById(string $id): ?Rule
    {
        try {
            return $this->ruleMapper->find((int)$id);
        } catch (Exception $e) {
            $this->logger->error('Error fetching rule: ' . $e->getMessage());
            return null;
        }
    }//end getRuleById()

    /**
     * Processes rules for an endpoint request.
     *
     * @param Endpoint    $endpoint The endpoint being processed.
     * @param IRequest    $request  The incoming request.
     * @param array       $data     Current request data.
     * @param string      $timing   The timing for rule execution (before/after).
     * @param string|null $objectId ID of the object being processed, if applicable.
     *
     * @return array|JSONResponse Returns modified data or error response if rule fails.
     *
     * @psalm-param array<string, mixed> $data
     * @psalm-return array<string, mixed>|JSONResponse
     */
    public function processRules(
        Endpoint $endpoint,
        IRequest $request,
        array $data,
        string $timing,
        ?string $objectId = null
    ): array|JSONResponse {
        $rules = $endpoint->getRules();
        if (empty($rules) === true) {
            return $data;
        }

        try {
            // Get all rules at once and sort by order
            $ruleEntities = array_filter(
                array_map(
                    fn($ruleId) => $this->getRuleById($ruleId),
                    $rules
                )
            );

            // Sort rules by order
            usort($ruleEntities, fn($a, $b) => $a->getOrder() - $b->getOrder());

            // Process each rule in order
            foreach ($ruleEntities as $rule) {
                // Skip if rule action doesn't match request method
                if (strtolower($rule->getAction()) !== strtolower($request->getMethod())) {
                    continue;
                }

                // Check rule conditions
                if ($this->checkRuleConditions($rule, $data) === false || $rule->getTiming() !== $timing) {
                    continue;
                }

                // Find appropriate handler
                $handler = $this->findHandlerForRule($rule);
                if ($handler === null) {
                    $this->logger->warning(sprintf('No handler found for rule type: %s', $rule->getType()));
                    continue;
                }

                // Process rule
                $result = $handler->process($rule, $data);

                // If result is JSONResponse, return error immediately
                if ($result instanceof JSONResponse) {
                    return $result;
                }

                // Update data with rule result
                $data = $result;
            }

            return $data;
        } catch (Exception $e) {
            $this->logger->error('Error processing rules: ' . $e->getMessage());
            return new JSONResponse(['error' => 'Rule processing failed: ' . $e->getMessage()], 500);
        }
    }//end processRules()

    /**
     * Checks if rule conditions are met.
     *
     * @param Rule  $rule The rule object containing conditions to be checked.
     * @param array $data The input data against which the conditions are evaluated.
     *
     * @return bool True if conditions are met, false otherwise.
     *
     * @throws Exception When there's an error evaluating conditions.
     *
     * @psalm-param array<string, mixed> $data
     */
    private function checkRuleConditions(Rule $rule, array $data): bool
    {
        $conditions = $rule->getConditions();
        if (empty($conditions) === true) {
            return true;
        }

        return JsonLogic::apply($conditions, $data) === true;
    }//end checkRuleConditions()

    /**
     * Find a handler for the given rule type.
     *
     * @param Rule $rule The rule to find a handler for.
     *
     * @return RuleHandlerInterface|null The handler that can process the rule, or null if none found.
     */
    private function findHandlerForRule(Rule $rule): ?RuleHandlerInterface
    {
        foreach ($this->handlers as $handler) {
            if ($handler->canProcess($rule)) {
                return $handler;
            }
        }

        return null;
    }//end findHandlerForRule()
}
