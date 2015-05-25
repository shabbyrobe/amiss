<?php
namespace Amiss\Test\Helper;

use Amiss\Sql\TableBuilder;

abstract class TestCase extends \PHPUnit_Framework_TestCase
{
    protected static $classScopeClasses = [];

    protected static function classScopeClassName($name, $split=false)
    {
        $bt = debug_backtrace();
        $btc = count($bt);
        $cur = 1;

        $ns = null;
        while ($cur < $btc) {
            if ($bt[$cur]['class'] != __CLASS__) {
                $ns = $bt[$cur]['class'];
                break;
            }
            $cur++;
        }
        if (!$ns) {
            throw new \Exception();
        }

        $fqcn = $ns.'\\'.$name;
        if ($split) {
            return [$ns, $fqcn];
        } else {
            return $fqcn;
        }
    }

    protected static function createClassScopeClass($name, $body)
    {
        list ($ns, $fqcn) = self::classScopeClassName($name, true);
        self::$classScopeClasses[$fqcn] = [$ns, $name, $fqcn];
        if (!class_exists($fqcn)) {
            eval("namespace $ns { $body }");
        }
        return $fqcn;
    }

    protected function createFnScopeClass($name, $body)
    {
        $bt = debug_backtrace();       
        $callClass = $bt[1]['class'];
        $callFn = $bt[1]['function'];
        $ns = $callClass.'\\'.$callFn;
        $fqcn = $ns.'\\'.$name;
        if (!class_exists($fqcn)) {
            eval("namespace $ns { $body }");
        }
        return $fqcn;
    }

    protected function createClass($fqcn, $src)
    {
        if (!class_exists($fqcn)) {
            eval($src);
        }
    }

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
