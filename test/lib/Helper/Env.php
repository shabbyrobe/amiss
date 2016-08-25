<?php
namespace Amiss\Test\Helper;

class Env
{
    public $connectionInfo;

    public static $instance;

    public static function instance()
    {
        if (!static::$instance) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    public function getConnectionInfo()
    {
        return $this->connectionInfo;
    }
}
