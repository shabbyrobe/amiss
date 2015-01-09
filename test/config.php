<?php
date_default_timezone_set('Australia/Melbourne');

require_once __DIR__.'/../vendor/autoload.php';
require_once __DIR__.'/../doc/demo/model.php';
require_once __DIR__.'/../doc/demo/ar.php';
require_once __DIR__.'/lib.php';

class TestConnector extends \Amiss\Sql\Connector
{
    public $calls = array();
    
    public function exec($statement, $params=null)
    {
        $this->calls[] = array($statement, array());
    }
    
    public function prepareWithResult($statement, $result, array $driverOptions=array())
    {
        $stmt = $this->prepare($statement, $driverOptions);
        $stmt->result = $result;
        return $stmt;
    }
    
    public function prepare($statement, array $driverOptions=array())
    {
        return new TestStatement($this, $statement, $driverOptions);
    }
    
    public function getLastCall()
    {
        return $this->calls[count($this->calls)-1];
    }
}

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

class TestMapper extends \Amiss\Mapper
{
    public $meta;
    
    function __construct($meta=array())
    {
        $this->meta = $meta;
    }
    
    function getMeta($class)
    {
        return isset($this->meta[$class]) ? $this->meta[$class] : null;
    }

    function createObject($meta, $row, $args=null) {}
    
    function populateObject($object, \stdClass $mapped, $meta=null) {}

    function fromObject($object, $meta=null, $context=null) {}
    
    function determineTypeHandler($type) {}
}    

class TestTypeHandler implements \Amiss\Type\Handler
{
    public $valueForDb;
    public $valueFromDb;
    public $columnType;
    
    public function __construct($data=array())
    {
        foreach ($data as $k=>$v) $this->$k = $v;
    }
    
    function prepareValueForDb($value, array $fieldInfo)
    {
        return $this->valueForDb;
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return $this->valueFromDb;
    }
    
    function createColumnType($engine)
    {
        return $this->columnType;
    }
}
