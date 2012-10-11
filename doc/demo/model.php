<?php
namespace Amiss\Demo;

abstract class Object
{
    public function __get($key)
    {
        throw new \Exception(sprintf("Property '%s' not defined", $key));
    }

    /**
     * Prevents a field from being set if it is not defined in the class.
     */
    public function __set($key, $value)
    {
        throw new \Exception(sprintf("Property '%s' not defined", $key));
    }

    /**
     * Prevents a field from being tested for existence if it is not defined in the class.
     */
    public function __isset($key)
    {
        throw new \Exception(sprintf("Property '%s' not defined", $key));
    }

    /**
     * Prevents a field from being unset if it is not defined in the class. 
     */
    public function __unset($key)
    {
        throw new \Exception(sprintf("Property '%s' not defined", $key));
    }
}

require(__DIR__.'/modeldefs.php');
