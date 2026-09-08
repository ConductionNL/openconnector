<?php

/**
 * Unit tests for the consolidated migration.
 *
 * @category Test
 * @package  OCA\Integriq\Tests\Unit\Migration
 *
 * @author    Conduction Development Team <info@conduction.nl>
 * @copyright 2026 Conduction B.V.
 * @license   EUPL-1.2 https://joinup.ec.europa.eu/collection/eupl/eupl-text-eupl-12
 *
 * @version GIT: <git_id>
 *
 * @link https://www.Integriq.nl
 */

declare(strict_types=1);

namespace OCA\Integriq\Tests\Unit\Migration;

use InvalidArgumentException;
use OCA\Integriq\Migration\Version2Date20260908000000;
use OCA\Integriq\Repair\MigrateLegacyStorage;
use OCP\App\IAppManager;
use OCP\DB\ISchemaWrapper;
use OCP\IAppConfig;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * Tests for Version2Date20260908000000.
 */
class ConsolidatedMigrationTest extends TestCase {
	/** @var array<int,string> */
	private array $statements = [];

	/** @var array<string,string> */
	private array $appConfigStore = [];

	private IDBConnection $db;

	private IAppConfig $appConfig;

	/**
	 * Wire the doubles shared by every test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->statements = [];
		$this->appConfigStore = [];

		$this->db = $this->createMock(IDBConnection::class);
		$this->db->method('executeStatement')->willReturnCallback(
			function (string $sql): int {
				$this->statements[] = $sql;
				return 0;
			}
		);

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appConfig->method('getValueString')->willReturnCallback(
			fn (string $app, string $key, string $default = ''): string => $this->appConfigStore[$key] ?? $default
		);
		$this->appConfig->method('setValueString')->willReturnCallback(
			function (string $app, string $key, string $value): bool {
				$this->appConfigStore[$key] = $value;
				return true;
			}
		);
	}

	/**
	 * Build the migration under test.
	 *
	 * @return Version2Date20260908000000
	 */
	private function migration(): Version2Date20260908000000 {
		return new Version2Date20260908000000(
			$this->appConfig,
			$this->db,
			$this->createMock(IAppManager::class),
			$this->createMock(LoggerInterface::class),
			$this->createMock(ContainerInterface::class)
		);
	}

	/**
	 * A schema double reporting the given tables as present.
	 *
	 * @param array<int,string> $present Unprefixed tables that exist.
	 *
	 * @return ISchemaWrapper
	 */
	private function schemaWith(array $present): ISchemaWrapper {
		$schema = $this->createMock(ISchemaWrapper::class);
		$schema->method('hasTable')->willReturnCallback(
			static fn (string $name): bool => in_array($name, $present, true)
		);
		return $schema;
	}

	/**
	 * Read a private constant off the migration.
	 *
	 * @return array<int,string>
	 */
	private function legacyTables(): array {
		/** @var array<int,string> $tables */
		$tables = (new ReflectionClass(Version2Date20260908000000::class))->getConstant('LEGACY_TABLES');
		return $tables;
	}

	/**
	 * This app owns no tables, so the schema step declares nothing.
	 *
	 * @return void
	 */
	public function testChangeSchemaDeclaresNothing(): void {
		$result = $this->migration()->changeSchema(
			$this->createMock(IOutput::class),
			fn () => $this->schemaWith([]),
			[]
		);

		$this->assertNull($result);
	}

	/**
	 * A fresh install has no legacy table, so there is nothing to drain or drop
	 * and the flag is simply recorded as done.
	 *
	 * @return void
	 */
	public function testAFreshInstallDropsNothingAndRecordsTheFlag(): void {
		$this->migration()->postSchemaChange(
			$this->createMock(IOutput::class),
			fn () => $this->schemaWith([]),
			[]
		);

		$this->assertSame([], $this->statements, 'nothing may be dropped when no legacy table exists');
		$this->assertSame('true', $this->appConfigStore['storage_migrated'] ?? null);
	}

	/**
	 * An instance that already completed the cutover drops every legacy table
	 * it still carries, without re-running the drain.
	 *
	 * @return void
	 */
	public function testAnAlreadyMigratedInstanceDropsEveryLegacyTable(): void {
		$this->appConfigStore['storage_migrated'] = 'true';
		$tables = $this->legacyTables();

		$this->migration()->postSchemaChange(
			$this->createMock(IOutput::class),
			fn () => $this->schemaWith($tables),
			[]
		);

		$this->assertCount(count($tables), $this->statements);
		foreach ($tables as $table) {
			$this->assertContains(
				sprintf('DROP TABLE IF EXISTS *PREFIX*%s', $table),
				$this->statements
			);
		}
	}

	/**
	 * Only the tables that are actually present are dropped.
	 *
	 * @return void
	 */
	public function testOnlyPresentTablesAreDropped(): void {
		$this->appConfigStore['storage_migrated'] = 'true';
		$present = array_slice($this->legacyTables(), 0, 3);

		$this->migration()->postSchemaChange(
			$this->createMock(IOutput::class),
			fn () => $this->schemaWith($present),
			[]
		);

		$this->assertCount(3, $this->statements);
	}

	/**
	 * This is the assertion the whole change turns on. A drain that could not
	 * run must leave every legacy table in place, because the rows in them have
	 * not reached OpenRegister and dropping would destroy them.
	 *
	 * The container is made to throw, which drives the drain's "failed to
	 * resolve services" branch. Do NOT simplify this to a plain mock and rely
	 * on OpenRegister being absent: it is absent from a bare clone but PRESENT
	 * in CI, where the app runs inside a server checkout with openregister
	 * enabled. A plain mock then returns null past the class_exists guard and
	 * the test dies on a null call instead of asserting anything.
	 *
	 * @return void
	 */
	public function testAnIncompleteDrainDropsNothingAndLeavesTheFlagUnset(): void {
		$tables = $this->legacyTables();

		$container = $this->createMock(ContainerInterface::class);
		$container->method('get')->willThrowException(new \RuntimeException('openregister unavailable'));

		$migration = new Version2Date20260908000000(
			$this->appConfig,
			$this->db,
			$this->createMock(IAppManager::class),
			$this->createMock(LoggerInterface::class),
			$container
		);

		$migration->postSchemaChange(
			$this->createMock(IOutput::class),
			fn () => $this->schemaWith($tables),
			[]
		);

		$this->assertSame([], $this->statements, 'a failed drain must not drop a single table');
		$this->assertArrayNotHasKey('storage_migrated', $this->appConfigStore);
	}

	/**
	 * One table failing to drop does not abandon the rest.
	 *
	 * @return void
	 */
	public function testAFailedDropIsLoggedAndTheRestStillRun(): void {
		$this->appConfigStore['storage_migrated'] = 'true';
		$tables = $this->legacyTables();
		$first = $tables[0];

		$db = $this->createMock(IDBConnection::class);
		$db->method('executeStatement')->willReturnCallback(
			function (string $sql) use ($first): int {
				if (str_contains($sql, $first)) {
					throw new \RuntimeException('table is locked');
				}

				$this->statements[] = $sql;
				return 0;
			}
		);

		$logger = $this->createMock(LoggerInterface::class);
		$logger->expects($this->once())->method('warning');

		$migration = new Version2Date20260908000000(
			$this->appConfig,
			$db,
			$this->createMock(IAppManager::class),
			$logger,
			$this->createMock(ContainerInterface::class)
		);

		$migration->postSchemaChange(
			$this->createMock(IOutput::class),
			fn () => $this->schemaWith($tables),
			[]
		);

		$this->assertCount(count($tables) - 1, $this->statements);
	}

	/**
	 * The identifier guard refuses a name that is not a bare identifier.
	 *
	 * @return void
	 */
	public function testANonIdentifierTableNameIsRefused(): void {
		$migration = $this->migration();
		$drop = (new ReflectionClass($migration))->getMethod('dropLegacyTables');
		$drop->setAccessible(true);

		$this->expectException(InvalidArgumentException::class);
		$drop->invoke($migration, $this->createMock(IOutput::class), ['bad"; DROP TABLE x; --']);
	}

	/**
	 * The migration and the repair step name the same fifteen tables.
	 *
	 * Two independently maintained lists. A table in one and not the other
	 * silently drops out of either the cutover check or the cleanup.
	 *
	 * @return void
	 */
	public function testTheTableListMatchesTheRepairStep(): void {
		$fromMigration = $this->legacyTables();
		$fromRepairStep = MigrateLegacyStorage::LEGACY_TABLES;

		sort($fromMigration);
		sort($fromRepairStep);

		$this->assertNotSame([], $fromMigration);
		$this->assertSame($fromRepairStep, $fromMigration);
	}
}
