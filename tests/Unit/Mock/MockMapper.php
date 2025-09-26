<?php

namespace OCA\OpenConnector\Tests\Unit\Mock;

/**
 * Mock Mapper class for testing
 * This avoids the signature compatibility issues with the original MockMapper
 */
class MockMapper
{
    public function __construct()
    {
        // Mock constructor
    }
    
    public function find($id)
    {
        return null;
    }
    
    public function findAll($ids = [])
    {
        return [];
    }
    
    public function insert($entity)
    {
        return $entity;
    }
    
    public function update($entity)
    {
        return $entity;
    }
    
    public function delete($entity)
    {
        return true;
    }
}
