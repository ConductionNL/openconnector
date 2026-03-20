<?php
/**
 * OpenConnector Metrics Service
 *
 * Service for collecting Prometheus metrics from OpenConnector tables.
 *
 * @category Service
 * @package  OCA\OpenConnector\Service
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2024 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.OpenConnector.nl
 */

declare(strict_types=1);

namespace OCA\OpenConnector\Service;

use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Service for collecting application metrics.
 *
 * Collects metrics from OpenConnector database tables and returns them
 * as structured arrays for Prometheus text exposition formatting.
 */
class MetricsService
{


    /**
     * MetricsService constructor.
     *
     * @param IConfig         $config The config service
     * @param IDBConnection   $db     The database connection
     * @param LoggerInterface $logger Logger for error handling
     */
    public function __construct(
        private readonly IConfig $config,
        private readonly IDBConnection $db,
        private readonly LoggerInterface $logger
    ) {

    }//end __construct()


    /**
     * Collect all metrics.
     *
     * @return array<string, mixed> Structured metrics data.
     */
    public function collect(): array
    {
        $metrics = [];

        $metrics['info'] = $this->collectInfo();
        $metrics['up'] = $this->checkUp();
        $metrics['sources'] = $this->collectSourcesByType();
        $metrics['calls'] = $this->collectCallsByStatus();
        $metrics['synchronizations'] = $this->collectSyncMetrics();
        $metrics['endpoints'] = $this->collectEndpointMetrics();
        $metrics['jobs'] = $this->collectJobMetrics();
        $metrics['mappings'] = $this->countTableSafe('openconnector_mappings');
        $metrics['rules'] = $this->countTableSafe('openconnector_rules');

        return $metrics;

    }//end collect()


    /**
     * Format metrics array as Prometheus text exposition.
     *
     * @param array<string, mixed> $metrics The collected metrics.
     *
     * @return string Prometheus text exposition formatted string.
     */
    public function format(array $metrics): string
    {
        $lines = [];

        // Info gauge.
        $info = $metrics['info'];
        $lines[] = '# HELP openconnector_info Application information';
        $lines[] = '# TYPE openconnector_info gauge';
        $lines[] = 'openconnector_info{version="' . $info['version'] . '",php_version="' . $info['php_version'] . '",nextcloud_version="' . $info['nextcloud_version'] . '"} 1';

        // Up gauge.
        $lines[] = '# HELP openconnector_up Whether the application is up';
        $lines[] = '# TYPE openconnector_up gauge';
        $lines[] = 'openconnector_up ' . ($metrics['up'] ? '1' : '0');

        // Sources by type.
        $lines[] = '# HELP openconnector_sources_total Total sources by type';
        $lines[] = '# TYPE openconnector_sources_total gauge';
        $sources = $metrics['sources'];
        if (empty($sources) === true) {
            $lines[] = 'openconnector_sources_total{type="rest"} 0';
        }

        foreach ($sources as $type => $count) {
            $lines[] = 'openconnector_sources_total{type="' . $type . '"} ' . $count;
        }

        // Calls by status.
        $lines[] = '# HELP openconnector_calls_total Total API calls by status';
        $lines[] = '# TYPE openconnector_calls_total counter';
        $calls = $metrics['calls'];
        if (empty($calls) === true) {
            $lines[] = 'openconnector_calls_total{status="200"} 0';
        }

        foreach ($calls as $status => $count) {
            $lines[] = 'openconnector_calls_total{status="' . $status . '"} ' . $count;
        }

        // Synchronizations.
        $sync = $metrics['synchronizations'];
        $lines[] = '# HELP openconnector_synchronizations_total Total synchronization configs';
        $lines[] = '# TYPE openconnector_synchronizations_total gauge';
        $lines[] = 'openconnector_synchronizations_total ' . $sync['total'];

        $lines[] = '# HELP openconnector_synchronization_runs_total Total sync log entries by result';
        $lines[] = '# TYPE openconnector_synchronization_runs_total counter';
        if (empty($sync['runs']) === true) {
            $lines[] = 'openconnector_synchronization_runs_total{status="success"} 0';
        }

        foreach ($sync['runs'] as $result => $count) {
            $lines[] = 'openconnector_synchronization_runs_total{status="' . $result . '"} ' . $count;
        }

        // Endpoints.
        $lines[] = '# HELP openconnector_endpoints_total Total registered endpoints';
        $lines[] = '# TYPE openconnector_endpoints_total gauge';
        $lines[] = 'openconnector_endpoints_total ' . $metrics['endpoints'];

        // Jobs.
        $jobs = $metrics['jobs'];
        $lines[] = '# HELP openconnector_jobs_total Total configured jobs';
        $lines[] = '# TYPE openconnector_jobs_total gauge';
        $lines[] = 'openconnector_jobs_total ' . $jobs['total'];

        $lines[] = '# HELP openconnector_job_runs_total Total job log entries by status';
        $lines[] = '# TYPE openconnector_job_runs_total counter';
        if (empty($jobs['runs']) === true) {
            $lines[] = 'openconnector_job_runs_total{status="success"} 0';
        }

        foreach ($jobs['runs'] as $status => $count) {
            $lines[] = 'openconnector_job_runs_total{status="' . $status . '"} ' . $count;
        }

        // Mappings and rules.
        $lines[] = '# HELP openconnector_mappings_total Total configured mappings';
        $lines[] = '# TYPE openconnector_mappings_total gauge';
        $lines[] = 'openconnector_mappings_total ' . $metrics['mappings'];

        $lines[] = '# HELP openconnector_rules_total Total configured rules';
        $lines[] = '# TYPE openconnector_rules_total gauge';
        $lines[] = 'openconnector_rules_total ' . $metrics['rules'];

        return implode("\n", $lines) . "\n";

    }//end format()


    /**
     * Collect application info.
     *
     * @return array{version: string, php_version: string, nextcloud_version: string}
     */
    private function collectInfo(): array
    {
        return [
            'version' => $this->config->getAppValue('openconnector', 'installed_version', '0.0.0'),
            'php_version' => PHP_VERSION,
            'nextcloud_version' => $this->config->getSystemValueString('version', '0.0.0'),
        ];

    }//end collectInfo()


    /**
     * Check if the application is up (database accessible).
     *
     * @return bool True if the application is healthy.
     */
    private function checkUp(): bool
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->createFunction('1'));
            $result = $qb->executeQuery();
            $result->closeCursor();
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Metrics: database health check failed', ['exception' => $e->getMessage()]);
            return false;
        }

    }//end checkUp()


    /**
     * Collect source counts grouped by type.
     *
     * @return array<string, int> Source counts keyed by type.
     */
    private function collectSourcesByType(): array
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('type', $qb->createFunction('COUNT(*) AS cnt'))
                ->from('openconnector_sources')
                ->groupBy('type');

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            $counts = [];
            foreach ($rows as $row) {
                $type = ($row['type'] !== null && $row['type'] !== '') ? strtolower($row['type']) : 'rest';
                $counts[$type] = (isset($counts[$type]) === true) ? $counts[$type] + (int) $row['cnt'] : (int) $row['cnt'];
            }

            return $counts;
        } catch (\Exception $e) {
            $this->logger->warning('Could not count sources for metrics', ['exception' => $e->getMessage()]);
            return [];
        }

    }//end collectSourcesByType()


    /**
     * Collect call log counts grouped by status code.
     *
     * @return array<string, int> Call counts keyed by status code.
     */
    private function collectCallsByStatus(): array
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('status_code', $qb->createFunction('COUNT(*) AS cnt'))
                ->from('openconnector_call_logs')
                ->groupBy('status_code');

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            $counts = [];
            foreach ($rows as $row) {
                $statusCode = $row['status_code'] ?? 'unknown';
                $counts[(string) $statusCode] = (int) $row['cnt'];
            }

            return $counts;
        } catch (\Exception $e) {
            $this->logger->warning('Could not count calls for metrics', ['exception' => $e->getMessage()]);
            return [];
        }

    }//end collectCallsByStatus()


    /**
     * Collect synchronization metrics.
     *
     * @return array{total: int, runs: array<string, int>} Sync metrics.
     */
    private function collectSyncMetrics(): array
    {
        $total = $this->countTableSafe('openconnector_synchronizations');

        $runs = [];
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('result', $qb->createFunction('COUNT(*) AS cnt'))
                ->from('openconnector_synchronization_logs')
                ->groupBy('result');

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            foreach ($rows as $row) {
                $resultLabel = ($row['result'] !== null && $row['result'] !== '') ? strtolower($row['result']) : 'unknown';
                $runs[$resultLabel] = (int) $row['cnt'];
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not count sync logs for metrics', ['exception' => $e->getMessage()]);
        }

        return ['total' => $total, 'runs' => $runs];

    }//end collectSyncMetrics()


    /**
     * Collect endpoint count metric.
     *
     * @return int Total number of registered endpoints.
     */
    private function collectEndpointMetrics(): int
    {
        return $this->countTableSafe('openconnector_endpoints');

    }//end collectEndpointMetrics()


    /**
     * Collect job metrics.
     *
     * @return array{total: int, runs: array<string, int>} Job metrics.
     */
    private function collectJobMetrics(): array
    {
        $total = $this->countTableSafe('openconnector_jobs');

        $runs = [];
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select('status', $qb->createFunction('COUNT(*) AS cnt'))
                ->from('openconnector_job_logs')
                ->groupBy('status');

            $result = $qb->executeQuery();
            $rows = $result->fetchAll();
            $result->closeCursor();

            foreach ($rows as $row) {
                $statusLabel = ($row['status'] !== null && $row['status'] !== '') ? strtolower($row['status']) : 'unknown';
                $runs[$statusLabel] = (int) $row['cnt'];
            }
        } catch (\Exception $e) {
            $this->logger->warning('Could not count job logs for metrics', ['exception' => $e->getMessage()]);
        }

        return ['total' => $total, 'runs' => $runs];

    }//end collectJobMetrics()


    /**
     * Safely count rows in a table with error fallback.
     *
     * @param string $tableName The table name.
     *
     * @return int The row count, or 0 on error.
     */
    private function countTableSafe(string $tableName): int
    {
        try {
            $qb = $this->db->getQueryBuilder();
            $qb->select($qb->createFunction('COUNT(*) AS cnt'))
                ->from($tableName);

            $result = $qb->executeQuery();
            $count = (int) $result->fetchOne();
            $result->closeCursor();

            return $count;
        } catch (\Exception $e) {
            $this->logger->warning('Could not count table ' . $tableName . ' for metrics', ['exception' => $e->getMessage()]);
            return 0;
        }

    }//end countTableSafe()


}//end class
