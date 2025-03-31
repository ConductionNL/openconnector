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

namespace OCA\OpenConnector\Service\EndpointHandler;

use OCA\OpenConnector\Db\Rule;
use OCP\AppFramework\Http\JSONResponse;

/**
 * Interface for rule handlers.
 *
 * This interface defines the contract for classes that process different
 * types of rules for endpoints, such as authentication, mapping, or synchronization.
 *
 * @category  Service
 * @package   OpenConnector
 * @author    Conduction Development Team <dev@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @link      https://OpenConnector.app
 */
interface RuleHandlerInterface
{
    /**
     * Determines if this handler can process the given rule.
     *
     * @param Rule $rule The rule to check.
     *
     * @return bool True if this handler can process the rule, false otherwise.
     */
    public function canProcess(Rule $rule): bool;

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
    public function process(Rule $rule, array $data): array|JSONResponse;
} 