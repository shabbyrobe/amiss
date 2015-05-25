<?php
namespace Amiss\Test\Helper;

use Amiss\Sql\TableBuilder;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function callProtected($class, $name)
    {
        $ref = new \ReflectionClass($class);
        $method = $ref->getMethod($name);
        $method->setAccessible(true);
        
        if ($method->isStatic()) $class = null;
        
        return $method->invokeArgs($class, array_slice(func_get_args(), 2));
    }
    
    protected function getProtected($class, $name)
    {
        $ref = new \ReflectionClass($class);
        $property = $ref->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($class);
    }
    
    protected function setProtected($class, $name, $value)
    {
        $ref = new \ReflectionClass($class);
        $property = $ref->getProperty($name);
        $property->setAccessible(true);
        return $property->setValue($class, $value);
    }
    
    public function matchesLoose($string)
    {
        return new LooseStringMatch($string);
    }
    
    public function assertLoose($expected, $value, $message=null)
    {
        $constraint = new LooseStringMatch($expected);
        
        if (!$message) {
            $message = "Failed asserting that value \"$value\" matches loose string \"$expected\"";
        }
        
        self::assertThat($value, $constraint, $message);
    }
    
    public function createRecordMemoryDb($class)
    {
        if ($class instanceof \Amiss\Active\Record) {
            forward_static_call(array($class, 'setManager'), $this->manager);
        }
        TableBuilder::create($this->manager->connector, $this->manager->mapper, $class);
    }
}
