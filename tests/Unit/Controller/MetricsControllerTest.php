<?php

declare(strict_types=1);

/**
 * MetricsControllerTest
 *
 * Unit tests for the MetricsController
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

use OCA\OpenConnector\Controller\MetricsController;
use OCA\OpenConnector\Service\MetricsService;
use OCP\AppFramework\Http\TextPlainResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the MetricsController
 *
 * Tests that the controller delegates to MetricsService and returns correct response.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class MetricsControllerTest extends TestCase
{

    /**
     * @var MetricsService&MockObject
     */
    private MetricsService $metricsService;

    /**
     * @var MetricsController
     */
    private MetricsController $controller;


    /**
     * Set up test fixtures.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $request = $this->createMock(IRequest::class);
        $this->metricsService = $this->createMock(MetricsService::class);

        $this->controller = new MetricsController(
            'openconnector',
            $request,
            $this->metricsService
        );

    }//end setUp()


    /**
     * Test that index returns TextPlainResponse.
     *
     * @return void
     */
    public function testIndexReturnsTextPlainResponse(): void
    {
        $metrics = ['info' => ['version' => '1.0.0']];
        $formatted = "openconnector_info{version=\"1.0.0\"} 1\n";

        $this->metricsService->expects($this->once())
            ->method('collect')
            ->willReturn($metrics);

        $this->metricsService->expects($this->once())
            ->method('format')
            ->with($metrics)
            ->willReturn($formatted);

        $response = $this->controller->index();

        $this->assertInstanceOf(TextPlainResponse::class, $response);

    }//end testIndexReturnsTextPlainResponse()


    /**
     * Test that index delegates to MetricsService.
     *
     * @return void
     */
    public function testIndexDelegatesToMetricsService(): void
    {
        $this->metricsService->expects($this->once())
            ->method('collect');
        $this->metricsService->expects($this->once())
            ->method('format');

        $this->metricsService->method('collect')->willReturn([]);
        $this->metricsService->method('format')->willReturn('');

        $this->controller->index();

    }//end testIndexDelegatesToMetricsService()


}//end class
