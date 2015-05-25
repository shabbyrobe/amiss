<?php
namespace Amiss\Test\Helper;

class DataTestCase extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->connections = [];
        
        $info = $this->getConnectionInfo();
        $this->createDb($info);
    }
    
    protected function createDb($info)
    {
        if ($info['engine'] == 'mysql') {
            if (!isset($info['dbName'])) {
                throw new \UnexpectedValueException("Please set dbName in MySQL connection info");
            }
            $result = $this->getConnector()->exec("CREATE DATABASE IF NOT EXISTS `{$info['dbName']}`");
            \Amiss\Test\Helper\Env::instance()->connectionInfo['statements'] = array(
                "USE `{$info['dbName']}`"
            );
        }
        elseif ($info['engine'] == 'pgsql') {
            $c = $this->getConnector();
            $c->exec('drop schema public cascade');
            $c->exec('create schema public');
        }
        elseif ($info['engine'] == 'sqlite') {
            // nothin!
        }
        else {
            throw new \UnexpectedValueException();
        }
    }

    public function tearDown()
    {
        parent::tearDown();
        
        $info = $this->getConnectionInfo();
        if ($info['engine'] == 'mysql') {
            $this->getConnector()->exec("DROP DATABASE IF EXISTS `{$info['dbName']}`");
            \Amiss\Test\Helper\Env::instance()->connectionInfo['statements'] = array();
        }
        foreach ($this->connections as $connector) {
            $connector->disconnect();
        }
    }

    public function getConnector()
    {
        $connection = $this->getConnectionInfo();
        $connector = \PDOK\Connector::create($connection);
        $hash = spl_object_hash($connector);
        if (isset($this->connections[$hash])) {
            throw new \UnexpectedValueException();
        }
        $this->connections[$hash] = $connector;
        return $connector;
    }
    
    public function getConnectionInfo()
    {
        return \Amiss\Test\Helper\Env::instance()->getConnectionInfo();
    }

    public function getEngine()
    {
        $info = $this->getConnectionInfo();
        return $info['engine'];
    }
    
    public function readSqlFile($name)
    {
        $name = strtr($name, array(
            '{engine}'=>$this->getEngine(),
        ));
        return file_get_contents($name);
    }
}
