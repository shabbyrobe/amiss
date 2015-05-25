<?php
namespace Amiss\Test;

class ConnectionFactory
{
    private $connections;
    private $activeCount = 0;
    private $info;

    function __construct($info)
    {
        if ($this->activeCount >= 1) {
            throw new \LogicException("Can't create multiples");
        }
        ++$this->activeCount;

        $this->info = $info;
        $connector = $this->getConnector();

        if ($info['engine'] == 'mysql') {
            if (!isset($info['dbName'])) {
                throw new \UnexpectedValueException("Please set dbName in MySQL connection info");
            }
            $result = $connector->exec("CREATE DATABASE IF NOT EXISTS `{$info['dbName']}`");
            $this->info['statements'][] = "USE `{$info['dbName']}`";
        }
        elseif ($info['engine'] == 'pgsql') {
            $connector->exec('drop schema public cascade')
                ->exec('create schema public');
        }
        elseif ($info['engine'] == 'sqlite') {
            // nothin!
        }
        else {
            throw new \UnexpectedValueException();
        }
    }

    public function getConnector()
    {
        $connection = $this->info;
        $connector = \PDOK\Connector::create($connection);
        $hash = spl_object_hash($connector);
        if (isset($this->connections[$hash])) {
            throw new \UnexpectedValueException();
        }
        $this->connections[$hash] = $connector;
        return $connector;
    }

    public function __destruct()
    {
        $info = $this->info;
        if ($info['engine'] == 'mysql') {
            $this->info['statements'] = [];
            $this->getConnector()->exec("DROP DATABASE IF EXISTS `{$info['dbName']}`");
        }
        foreach ($this->connections as $connector) {
            $connector->disconnect();
        }
        --$this->activeCount;
    }
}

