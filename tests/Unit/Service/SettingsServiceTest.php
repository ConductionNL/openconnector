<?php

namespace OCA\OpenConnector\Tests\Unit\Service;

use OCA\OpenConnector\Service\SettingsService;
use OCP\IDBConnection;
use OCP\IAppConfig;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SettingsServiceTest extends TestCase
{
    private IDBConnection $db;
    private IAppConfig $appConfig;
    private LoggerInterface $logger;
    private SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->db = $this->createMock(IDBConnection::class);
        $this->appConfig = $this->createMock(IAppConfig::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->settingsService = new SettingsService(
            $this->db,
            $this->appConfig,
            $this->logger
        );
    }

    public function testGetStats(): void
    {
        $stats = $this->settingsService->getStats();
        
        $this->assertIsArray($stats);
    }

    public function testGetSettings(): void
    {
        $settings = $this->settingsService->getSettings();
        
        $this->assertIsArray($settings);
    }

    public function testUpdateSettings(): void
    {
        $newSettings = ['test' => 'value'];
        
        $result = $this->settingsService->updateSettings($newSettings);
        
        $this->assertIsArray($result);
    }

    public function testRebase(): void
    {
        $result = $this->settingsService->rebase();
        
        $this->assertIsArray($result);
    }

    public function testGetStatsWithException(): void
    {
        $this->db->method('getQueryBuilder')
            ->willThrowException(new \Exception('Database error'));
        
        $stats = $this->settingsService->getStats();
        
        $this->assertIsArray($stats);
    }
}
