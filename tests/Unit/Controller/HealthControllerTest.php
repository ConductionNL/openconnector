<?php

declare(strict_types=1);

/**
 * HealthControllerTest
 *
 * Unit tests for the HealthController
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

use OCA\OpenConnector\Controller\HealthController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the HealthController
 *
 * Tests health check endpoint for ok, degraded, and error states.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class HealthControllerTest extends TestCase
{

    /**
     * @var IDBConnection&MockObject
     */
    private IDBConnection $db;

    /**
     * @var LoggerInterface&MockObject
     */
    private LoggerInterface $logger;

    /**
     * @var HealthController
     */
    private HealthController $controller;


    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $request      = $this->createMock(IRequest::class);
        $this->db     = $this->createMock(IDBConnection::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->controller = new HealthController(
            'openconnector',
            $request,
            $this->db,
            $this->logger
        );

    }//end setUp()


    /**
     * Test health check returns ok when all checks pass.
     *
     * @return void
     */
    public function testIndexReturnsOkWhenHealthy(): void
    {
        $result = $this->createMock(IResult::class);
        $result->method('closeCursor');

        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('createFunction')->willReturn('1');
        $queryBuilder->method('executeQuery')->willReturn($result);

        $this->db->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $response = $this->controller->index();

        $this->assertInstanceOf(JSONResponse::class, $response);
        $data = $response->getData();
        $this->assertSame('ok', $data['status']);
        $this->assertSame('ok', $data['checks']['database']);
        $this->assertSame('ok', $data['checks']['sources_table']);

    }//end testIndexReturnsOkWhenHealthy()


    /**
     * Test health check returns error when database is down.
     *
     * @return void
     */
    public function testIndexReturnsErrorWhenDatabaseDown(): void
    {
        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('createFunction')->willReturn('1');
        $queryBuilder->method('executeQuery')
            ->willThrowException(new \Exception('Connection refused'));

        $this->db->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $response = $this->controller->index();

        $data = $response->getData();
        $this->assertSame('error', $data['status']);
        $this->assertSame('error', $data['checks']['database']);

    }//end testIndexReturnsErrorWhenDatabaseDown()


    /**
     * Test health check returns degraded when sources table missing.
     *
     * @return void
     */
    public function testIndexReturnsDegradedWhenTableMissing(): void
    {
        $result = $this->createMock(IResult::class);
        $result->method('closeCursor');

        $callCount    = 0;
        $queryBuilder = $this->createMock(IQueryBuilder::class);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('createFunction')->willReturn('1');
        $queryBuilder->method('executeQuery')
            ->willReturnCallback(function () use (&$callCount, $result) {
                $callCount++;
                if ($callCount === 1) {
                    // Database check passes.
                    return $result;
                }

                // Sources table check fails.
                throw new \Exception('Table not found');
            });

        $this->db->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $response = $this->controller->index();

        $data = $response->getData();
        $this->assertSame('degraded', $data['status']);
        $this->assertSame('ok', $data['checks']['database']);
        $this->assertSame('error', $data['checks']['sources_table']);

    }//end testIndexReturnsDegradedWhenTableMissing()


}//end class
