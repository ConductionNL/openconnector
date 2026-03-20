<?php

declare(strict_types=1);

/**
 * MetricsServiceTest
 *
 * Unit tests for the MetricsService
 *
 * @category   Test
 * @package    OCA\OpenConnector\Tests\Unit\Service
 * @author     Conduction.nl <info@conduction.nl>
 * @copyright  Conduction.nl 2024
 * @license    EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 * @version    1.0.0
 * @link       https://github.com/ConductionNL/openconnector
 */

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Service\MetricsService;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IConfig;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the MetricsService
 *
 * Tests metric collection, formatting, and error handling.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Service
 */
class MetricsServiceTest extends TestCase
{

    /**
     * @var IConfig&MockObject
     */
    private IConfig $config;

    /**
     * @var IDBConnection&MockObject
     */
    private IDBConnection $db;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var MetricsService
     */
    private MetricsService $service;


    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(IConfig::class);
        $this->db     = $this->createMock(IDBConnection::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new MetricsService(
            $this->config,
            $this->db,
            $this->logger
        );

    }//end setUp()


    /**
     * Test that collect returns all expected metric keys.
     *
     * @return void
     */
    public function testCollectReturnsAllMetricKeys(): void
    {
        $this->setupDefaultMocks();

        $metrics = $this->service->collect();

        $this->assertArrayHasKey('info', $metrics);
        $this->assertArrayHasKey('up', $metrics);
        $this->assertArrayHasKey('sources', $metrics);
        $this->assertArrayHasKey('calls', $metrics);
        $this->assertArrayHasKey('synchronizations', $metrics);
        $this->assertArrayHasKey('endpoints', $metrics);
        $this->assertArrayHasKey('jobs', $metrics);
        $this->assertArrayHasKey('mappings', $metrics);
        $this->assertArrayHasKey('rules', $metrics);

    }//end testCollectReturnsAllMetricKeys()


    /**
     * Test that info contains version information.
     *
     * @return void
     */
    public function testCollectInfoContainsVersions(): void
    {
        $this->config->method('getAppValue')
            ->willReturn('2.1.0');
        $this->config->method('getSystemValueString')
            ->willReturn('30.0.0');

        $this->setupDefaultDbMock();

        $metrics = $this->service->collect();
        $info    = $metrics['info'];

        $this->assertSame('2.1.0', $info['version']);
        $this->assertSame(PHP_VERSION, $info['php_version']);
        $this->assertSame('30.0.0', $info['nextcloud_version']);

    }//end testCollectInfoContainsVersions()


    /**
     * Test that database error in one metric does not break others.
     *
     * @return void
     */
    public function testDatabaseErrorFallbackToZero(): void
    {
        $this->setupDefaultMocks();

        // Make the DB throw on every query after the health check.
        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('createFunction')->willReturn('COUNT(*) AS cnt');

        $callCount = 0;
        $queryBuilder->method('executeQuery')
            ->willReturnCallback(function () use (&$callCount) {
                $callCount++;
                // Let the first call (health check) succeed.
                if ($callCount === 1) {
                    $result = $this->createMock(IResult::class);
                    $result->method('closeCursor');
                    return $result;
                }

                // All subsequent calls throw.
                throw new \Exception('Database error');
            });

        $this->db->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $metrics = $this->service->collect();

        // Should still have all keys with zero/empty fallbacks.
        $this->assertArrayHasKey('sources', $metrics);
        $this->assertArrayHasKey('mappings', $metrics);
        $this->assertSame(0, $metrics['mappings']);
        $this->assertSame(0, $metrics['rules']);

    }//end testDatabaseErrorFallbackToZero()


    /**
     * Test that format produces valid Prometheus text.
     *
     * @return void
     */
    public function testFormatProducesValidPrometheusText(): void
    {
        $metrics = [
            'info'               => ['version' => '1.0.0', 'php_version' => '8.3.0', 'nextcloud_version' => '30.0.0'],
            'up'                 => true,
            'sources'            => ['rest' => 5, 'soap' => 2],
            'calls'              => ['200' => 100, '500' => 3],
            'synchronizations'   => ['total' => 10, 'runs' => ['success' => 400]],
            'endpoints'          => 15,
            'jobs'               => ['total' => 5, 'runs' => ['success' => 100, 'error' => 10]],
            'mappings'           => 20,
            'rules'              => 8,
        ];

        $output = $this->service->format($metrics);

        $this->assertStringContainsString('openconnector_info{version="1.0.0"', $output);
        $this->assertStringContainsString('openconnector_up 1', $output);
        $this->assertStringContainsString('openconnector_sources_total{type="rest"} 5', $output);
        $this->assertStringContainsString('openconnector_calls_total{status="200"} 100', $output);
        $this->assertStringContainsString('openconnector_synchronizations_total 10', $output);
        $this->assertStringContainsString('openconnector_endpoints_total 15', $output);
        $this->assertStringContainsString('openconnector_jobs_total 5', $output);
        $this->assertStringContainsString('openconnector_job_runs_total{status="success"} 100', $output);
        $this->assertStringContainsString('openconnector_mappings_total 20', $output);
        $this->assertStringContainsString('openconnector_rules_total 8', $output);

    }//end testFormatProducesValidPrometheusText()


    /**
     * Test format with empty data produces zero-value fallbacks.
     *
     * @return void
     */
    public function testFormatWithEmptyDataProducesZeroFallbacks(): void
    {
        $metrics = [
            'info'               => ['version' => '0.0.0', 'php_version' => '8.3.0', 'nextcloud_version' => '30.0.0'],
            'up'                 => false,
            'sources'            => [],
            'calls'              => [],
            'synchronizations'   => ['total' => 0, 'runs' => []],
            'endpoints'          => 0,
            'jobs'               => ['total' => 0, 'runs' => []],
            'mappings'           => 0,
            'rules'              => 0,
        ];

        $output = $this->service->format($metrics);

        $this->assertStringContainsString('openconnector_up 0', $output);
        $this->assertStringContainsString('openconnector_sources_total{type="rest"} 0', $output);
        $this->assertStringContainsString('openconnector_calls_total{status="200"} 0', $output);
        $this->assertStringContainsString('openconnector_synchronization_runs_total{status="success"} 0', $output);
        $this->assertStringContainsString('openconnector_job_runs_total{status="success"} 0', $output);

    }//end testFormatWithEmptyDataProducesZeroFallbacks()


    /**
     * Set up default config mocks.
     *
     * @return void
     */
    private function setupDefaultMocks(): void
    {
        $this->config->method('getAppValue')
            ->willReturn('0.0.0');
        $this->config->method('getSystemValueString')
            ->willReturn('0.0.0');

        $this->setupDefaultDbMock();

    }//end setupDefaultMocks()


    /**
     * Set up default database mock that returns empty results.
     *
     * @return void
     */
    private function setupDefaultDbMock(): void
    {
        $result = $this->createMock(IResult::class);
        $result->method('fetchAll')->willReturn([]);
        $result->method('fetchOne')->willReturn('0');
        $result->method('closeCursor');

        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('groupBy')->willReturnSelf();
        $queryBuilder->method('createFunction')->willReturn('COUNT(*) AS cnt');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $this->db->method('getQueryBuilder')
            ->willReturn($queryBuilder);

    }//end setupDefaultDbMock()


}//end class
