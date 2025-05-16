<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OpenConnector\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Migration step to add configurations and slug columns to all necessary tables.
 *
 * @package OCA\OpenConnector\Migration
 * @category Migration
 * @author OpenConnector Team
 * @copyright 2024 OpenConnector
 * @license AGPL-3.0
 * @version 1.0.0
 * @link https://github.com/OpenConnector/openconnector
 */
class Version1Date20250515232835 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/**
		 * @var ISchemaWrapper $schema
		 */
		$schema = $schemaClosure();

		// List of tables that need the new columns
		$tables = [
			'openconnector_sources',
			'openconnector_endpoints',
			'openconnector_mappings',
			'openconnector_rules',
			'openconnector_jobs',
			'openconnector_synchronizations'
		];

		// Add configurations and slug columns to each table
		foreach ($tables as $tableName) {
			if ($schema->hasTable($tableName)) {
				$table = $schema->getTable($tableName);

				// Add configurations column if it doesn't exist
				if (!$table->hasColumn('configurations')) {
					$table->addColumn('configurations', Types::JSON)
						->setNotnull(false)
						->setDefault('[]');
				}

				// Add slug column if it doesn't exist
				if (!$table->hasColumn('slug')) {
					$table->addColumn('slug', Types::STRING, [
						'length' => 255,
						'notnull' => false,
						'default' => null
					]);

					// Add index for the slug column
					$table->addIndex(['slug'], 'idx_' . $tableName . '_slug');
				}
			}
		}

		// Add status column to synchronizations table if it doesn't exist
		if ($schema->hasTable('openconnector_synchronizations')) {
			$table = $schema->getTable('openconnector_synchronizations');
			if (!$table->hasColumn('status')) {
				$table->addColumn('status', Types::STRING, [
					'length' => 255,
					'notnull' => false,
					'default' => null
				]);
			}
		}

		// Add status column to jobs table if it doesn't exist
		if ($schema->hasTable('openconnector_jobs')) {
			$table = $schema->getTable('openconnector_jobs');
			if (!$table->hasColumn('status')) {
				$table->addColumn('status', Types::STRING, [
					'length' => 255,
					'notnull' => false,
					'default' => null
				]);
			}
		}

		return $schema;
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
	}
}
