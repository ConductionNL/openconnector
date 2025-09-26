<?php

namespace OCA\OpenConnector\Tests\Unit\Mock;

/**
 * Mock Entity class for testing
 * This provides a base class for entity testing
 */
class MockEntity
{
    protected $id;
    protected $uuid;
    protected $created;
    protected $updated;
    
    public function __construct()
    {
        $this->id = null;
        $this->uuid = uniqid();
        $this->created = new \DateTime();
        $this->updated = new \DateTime();
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    
    public function getUuid()
    {
        return $this->uuid;
    }
    
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }
    
    public function getCreated()
    {
        return $this->created;
    }
    
    public function setCreated($created)
    {
        $this->created = $created;
        return $this;
    }
    
    public function getUpdated()
    {
        return $this->updated;
    }
    
    public function setUpdated($updated)
    {
        $this->updated = $updated;
        return $this;
    }
}
