<?php

declare(strict_types=1);

/**
 * RulesControllerTest
 * 
 * Unit tests for the RulesController
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

use OCA\OpenConnector\Controller\RulesController;
use OCA\OpenConnector\Service\ObjectService;
use OCA\OpenConnector\Service\SearchService;
use OCA\OpenConnector\Db\Rule;
use OCA\OpenConnector\Db\RuleMapper;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IAppConfig;
use OCP\IRequest;
use OCP\AppFramework\Db\DoesNotExistException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the RulesController
 *
 * This test class covers all functionality of the RulesController
 * including rule listing, creation, updates, deletion, and individual rule retrieval.
 *
 * @category Test
 * @package  OCA\OpenConnector\Tests\Unit\Controller
 */
class RulesControllerTest extends TestCase
{
    /**
     * The RulesController instance being tested
     *
     * @var RulesController
     */
    private RulesController $controller;

    /**
     * Mock request object
     *
     * @var MockObject|IRequest
     */
    private MockObject $request;

    /**
     * Mock app config
     *
     * @var MockObject|IAppConfig
     */
    private MockObject $config;

    /**
     * Mock rule mapper
     *
     * @var MockObject|RuleMapper
     */
    private MockObject $ruleMapper;

    /**
     * Set up test environment before each test
     *
     * This method initializes all mocks and the controller instance
     * for testing purposes.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mock objects for all dependencies
        $this->request = $this->createMock(IRequest::class);
        $this->config = $this->createMock(IAppConfig::class);
        $this->ruleMapper = $this->createMock(RuleMapper::class);

        // Initialize the controller with mocked dependencies
        $this->controller = new RulesController(
            'openconnector',
            $this->request,
            $this->config,
            $this->ruleMapper
        );
    }

    /**
     * Test successful page rendering
     *
     * This test verifies that the page() method returns a proper TemplateResponse.
     *
     * @return void
     */
    public function testPageSuccessful(): void
    {
        // Execute the method
        $response = $this->controller->page();

        // Assert response is a TemplateResponse
        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertEquals('index', $response->getTemplateName());
        $this->assertEquals([], $response->getParams());
    }

    /**
     * Test successful retrieval of all rules
     *
     * This test verifies that the index() method returns correct rule data
     * with search functionality.
     *
     * @return void
     */
    public function testIndexSuccessful(): void
    {
        // Setup mock request parameters
        $filters = ['search' => 'test', 'limit' => 10];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($filters);

        // Create mock services
        $objectService = $this->createMock(ObjectService::class);
        $searchService = $this->createMock(SearchService::class);

        // Mock search service methods
        $searchService->expects($this->once())
            ->method('createMySQLSearchParams')
            ->with($filters)
            ->willReturn(['search' => 'test']);

        $searchService->expects($this->once())
            ->method('createMySQLSearchConditions')
            ->with($filters, ['name', 'description'])
            ->willReturn(['conditions' => 'name LIKE %test%']);

        $searchService->expects($this->once())
            ->method('unsetSpecialQueryParams')
            ->with($filters)
            ->willReturn(['limit' => 10]);

        // Mock rule mapper
        $expectedRules = [
            new Rule(),
            new Rule()
        ];
        $this->ruleMapper->expects($this->once())
            ->method('findAll')
            ->with(
                null, // limit
                null, // offset
                ['limit' => 10], // filters
                ['conditions' => 'name LIKE %test%'], // searchConditions
                ['search' => 'test'] // searchParams
            )
            ->willReturn($expectedRules);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedRules], $response->getData());
    }

    /**
     * Test successful retrieval of a single rule
     *
     * This test verifies that the show() method returns correct rule data
     * for a valid rule ID.
     *
     * @return void
     */
    public function testShowSuccessful(): void
    {
        $ruleId = '123';
        $expectedRule = new Rule();
        $expectedRule->setId((int) $ruleId);
        $expectedRule->setName('Test Rule');

        // Mock rule mapper to return the expected rule
        $this->ruleMapper->expects($this->once())
            ->method('find')
            ->with((int) $ruleId)
            ->willReturn($expectedRule);

        // Execute the method
        $response = $this->controller->show($ruleId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedRule, $response->getData());
    }

    /**
     * Test rule retrieval with non-existent ID
     *
     * This test verifies that the show() method returns a 404 error
     * when the rule ID does not exist.
     *
     * @return void
     */
    public function testShowWithNonExistentId(): void
    {
        $ruleId = '999';

        // Mock rule mapper to throw DoesNotExistException
        $this->ruleMapper->expects($this->once())
            ->method('find')
            ->with((int) $ruleId)
            ->willThrowException(new DoesNotExistException('Rule not found'));

        // Execute the method
        $response = $this->controller->show($ruleId);

        // Assert response is error
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['error' => 'Not Found'], $response->getData());
        $this->assertEquals(404, $response->getStatus());
    }

    /**
     * Test successful rule creation
     *
     * This test verifies that the create() method creates a new rule
     * and returns the created rule data.
     *
     * @return void
     */
    public function testCreateSuccessful(): void
    {
        $ruleData = [
            'name' => 'New Rule',
            'description' => 'A new test rule',
            'conditions' => ['field1' => 'value1'],
            '_internal' => 'should_be_removed',
            'id' => '999' // should be removed
        ];

        $expectedRule = new Rule();
        $expectedRule->setName('New Rule');
        $expectedRule->setDescription('A new test rule');

        // Mock request to return rule data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($ruleData);

        // Mock rule mapper to return the created rule
        $this->ruleMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['name' => 'New Rule', 'description' => 'A new test rule', 'conditions' => ['field1' => 'value1']])
            ->willReturn($expectedRule);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedRule, $response->getData());
    }

    /**
     * Test successful rule update
     *
     * This test verifies that the update() method updates an existing rule
     * and returns the updated rule data.
     *
     * @return void
     */
    public function testUpdateSuccessful(): void
    {
        $ruleId = 123;
        $updateData = [
            'name' => 'Updated Rule',
            'description' => 'An updated test rule',
            '_internal' => 'should_be_removed',
            'id' => '999' // should be removed
        ];

        $updatedRule = new Rule();
        $updatedRule->setId($ruleId);
        $updatedRule->setName('Updated Rule');
        $updatedRule->setDescription('An updated test rule');

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock rule mapper to return updated rule
        $this->ruleMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($ruleId, ['name' => 'Updated Rule', 'description' => 'An updated test rule'])
            ->willReturn($updatedRule);

        // Execute the method
        $response = $this->controller->update($ruleId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedRule, $response->getData());
    }

    /**
     * Test successful rule deletion
     *
     * This test verifies that the destroy() method deletes a rule
     * and returns an empty response.
     *
     * @return void
     */
    public function testDestroySuccessful(): void
    {
        $ruleId = 123;
        $existingRule = new Rule();
        $existingRule->setId($ruleId);
        $existingRule->setName('Test Rule');

        // Mock rule mapper to return existing rule and handle deletion
        $this->ruleMapper->expects($this->once())
            ->method('find')
            ->with($ruleId)
            ->willReturn($existingRule);

        $this->ruleMapper->expects($this->once())
            ->method('delete')
            ->with($existingRule);

        // Execute the method
        $response = $this->controller->destroy($ruleId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals([], $response->getData());
    }

    /**
     * Test index method with empty filters
     *
     * This test verifies that the index() method handles empty filters correctly.
     *
     * @return void
     */
    public function testIndexWithEmptyFilters(): void
    {
        // Setup mock request parameters with empty filters
        $filters = [];
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($filters);

        // Create mock services
        $objectService = $this->createMock(ObjectService::class);
        $searchService = $this->createMock(SearchService::class);

        // Mock search service methods
        $searchService->expects($this->once())
            ->method('createMySQLSearchParams')
            ->with($filters)
            ->willReturn([]);

        $searchService->expects($this->once())
            ->method('createMySQLSearchConditions')
            ->with($filters, ['name', 'description'])
            ->willReturn([]);

        $searchService->expects($this->once())
            ->method('unsetSpecialQueryParams')
            ->with($filters)
            ->willReturn([]);

        // Mock rule mapper
        $expectedRules = [];
        $this->ruleMapper->expects($this->once())
            ->method('findAll')
            ->with(null, null, [], [], [])
            ->willReturn($expectedRules);

        // Execute the method
        $response = $this->controller->index($objectService, $searchService);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals(['results' => $expectedRules], $response->getData());
    }

    /**
     * Test rule creation with data filtering
     *
     * This test verifies that the create() method properly filters out
     * internal fields and ID fields.
     *
     * @return void
     */
    public function testCreateWithDataFiltering(): void
    {
        $ruleData = [
            'name' => 'Filtered Rule',
            '_internal_field' => 'should_be_removed',
            '_another_internal' => 'also_removed',
            'id' => '999',
            'description' => 'A rule with filtered data'
        ];

        $expectedRule = new Rule();
        $expectedRule->setName('Filtered Rule');
        $expectedRule->setDescription('A rule with filtered data');

        // Mock request to return rule data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($ruleData);

        // Mock rule mapper to return the created rule
        $this->ruleMapper->expects($this->once())
            ->method('createFromArray')
            ->with(['name' => 'Filtered Rule', 'description' => 'A rule with filtered data'])
            ->willReturn($expectedRule);

        // Execute the method
        $response = $this->controller->create();

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($expectedRule, $response->getData());
    }

    /**
     * Test rule update with data filtering
     *
     * This test verifies that the update() method properly filters out
     * internal fields and ID fields.
     *
     * @return void
     */
    public function testUpdateWithDataFiltering(): void
    {
        $ruleId = 123;
        $updateData = [
            'name' => 'Updated Filtered Rule',
            '_internal_field' => 'should_be_removed',
            '_another_internal' => 'also_removed',
            'id' => '999',
            'description' => 'An updated rule with filtered data'
        ];

        $updatedRule = new Rule();
        $updatedRule->setId($ruleId);
        $updatedRule->setName('Updated Filtered Rule');
        $updatedRule->setDescription('An updated rule with filtered data');

        // Mock request to return update data
        $this->request->expects($this->once())
            ->method('getParams')
            ->willReturn($updateData);

        // Mock rule mapper to return updated rule
        $this->ruleMapper->expects($this->once())
            ->method('updateFromArray')
            ->with($ruleId, ['name' => 'Updated Filtered Rule', 'description' => 'An updated rule with filtered data'])
            ->willReturn($updatedRule);

        // Execute the method
        $response = $this->controller->update($ruleId);

        // Assert response is successful
        $this->assertInstanceOf(JSONResponse::class, $response);
        $this->assertEquals($updatedRule, $response->getData());
    }
}
