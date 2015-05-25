<?php
namespace Amiss\Test\Helper;

class TestStatement
{
    public $queryString;
    public $params = array();
    public $driverOptions = array();
    public $result;
    
    public function __construct($connector, $statement, $driverOptions)
    {
        $this->connector = $connector;
        $this->driverOptions = $driverOptions;
        $this->queryString = $statement;
    }
    
    public function execute()
    {
        $this->connector->calls[] = array($this->queryString, $this->params);
        $this->params = array();
        return $this;
    }
    
    public function fetchColumn()
    {
        $result = $this->result;
        $this->result = null;
        return $result;
    }
}

