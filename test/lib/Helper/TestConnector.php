<?php
namespace Amiss\Test\Helper;

class TestConnector extends \PDOK\Connector
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

