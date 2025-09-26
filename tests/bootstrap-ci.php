<?php

// CI-specific bootstrap for non-Db tests only
// This provides minimal OCP class mocks without MockMapper signature issues

// Set up basic autoloading
require_once __DIR__ . '/../vendor/autoload.php';

// Mock only the essential OCP classes needed for non-Db tests
if (!class_exists('OCP\AppFramework\Db\QBMapper')) {
    class_alias('OCA\OpenConnector\Tests\Unit\Mock\MockQBMapper', 'OCP\AppFramework\Db\QBMapper');
}

if (!class_exists('OCP\AppFramework\Db\Mapper')) {
    class_alias('OCA\OpenConnector\Tests\Unit\Mock\MockMapper', 'OCP\AppFramework\Db\Mapper');
}

if (!class_exists('OCP\AppFramework\Db\Entity')) {
    class_alias('OCA\OpenConnector\Tests\Unit\Mock\MockEntity', 'OCP\AppFramework\Db\Entity');
}

// Create minimal mock classes for non-Db tests
class MockQBMapper {
    public function __construct() {}
    public function find($id) { return null; }
    public function findAll($ids = []) { return []; }
    public function insert($entity) { return $entity; }
    public function update($entity) { return $entity; }
    public function delete($entity) { return true; }
}

class MockMapper {
    public function __construct() {}
    public function find($id) { return null; }
    public function findAll($ids = []) { return []; }
    public function insert($entity) { return $entity; }
    public function update($entity) { return $entity; }
    public function delete($entity) { return true; }
}

class MockEntity {
    protected $id;
    public function __construct() { $this->id = null; }
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; return $this; }
}
