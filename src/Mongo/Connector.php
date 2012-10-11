<?php
namespace Amiss\Mongo;

/**
 * PHP's default Mongo class makes a connection upon instantiation. 
 * This wrapper defers creation of the Mongo instance until it is used.
 */
class Connector
{
    private $args;
    private $mongo;
    
    public function __construct()
    {
        $this->args = func_get_args();
    }
    
    protected function getMongo()
    {
        if (!$this->mongo) {
            $rc = new \ReflectionClass('Mongo');
            $this->mongo = $rc->newInstanceArgs($this->args);
        }
        return $this->mongo;
    }
    
    public function __call($name, $args)
    {
        return call_user_func_array(array($this->getMongo(), $name), $args);
    }
    
    public function __get($name)
    {
        return $this->getMongo()->$name;
    }
    
    public function __set($name, $value)
    {
        $this->getMongo()->$name = $value;
    }
    
    public function __isset($name)
    {
        return isset($this->getMongo()->$name);
    }
    
    public function __unset($name)
    {
        unset($this->getMongo()->$name);
    }
}
