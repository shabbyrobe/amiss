<?php

use Amiss\Sql\TableBuilder;

class DummyClass
{
    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __unset($name)
    {
        $this->$name = null;
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }
}

abstract class CustomTestCase extends PHPUnit_Framework_TestCase
{
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
        $ref = new ReflectionClass($class);
        $method = $ref->getMethod($name);
        $method->setAccessible(true);
        
        if ($method->isStatic()) $class = null;
        
        return $method->invokeArgs($class, array_slice(func_get_args(), 2));
    }
    
    protected function getProtected($class, $name)
    {
        $ref = new ReflectionClass($class);
        $property = $ref->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($class);
    }
    
    protected function setProtected($class, $name, $value)
    {
        $ref = new ReflectionClass($class);
        $property = $ref->getProperty($name);
        $property->setAccessible(true);
        return $property->setValue($class, $value);
    }
    
    public function matchesLoose($string)
    {
        return new \LooseStringMatch($string);
    }
    
    public function assertLoose($expected, $value, $message=null)
    {
        $constraint = new \LooseStringMatch($expected);
        
        if (!$message) {
            $message = "Failed asserting that value \"$value\" matches loose string \"$expected\"";
        }
        
        self::assertThat($value, $constraint, $message);
    }
    
    public function createRecordMemoryDb($class)
    {
        if ($class instanceof \Amiss\Active\Record)
            forward_static_call(array($class, 'setManager'), $this->manager);

        TableBuilder::create($this->manager->connector, $this->manager->mapper, $class);
    }
}

class DataTestCase extends CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $info = $this->getConnectionInfo();
        if ($info['engine'] == 'mysql') {
            if (!isset($info['dbName']))
                throw new \UnexpectedValueException("Please set dbName in MySQL connection info");
            
            $result = $this->getConnector()->exec("CREATE DATABASE IF NOT EXISTS `{$info['dbName']}`");
            TestApp::instance()->connectionInfo['statements'] = array(
                "USE `{$info['dbName']}`"
            );
        }
    }
    
    public function tearDown()
    {
        parent::tearDown();
        
        $info = $this->getConnectionInfo();
        if ($info['engine'] == 'mysql') {
            $this->getConnector()->exec("DROP DATABASE IF EXISTS `{$info['dbName']}`");
            TestApp::instance()->connectionInfo['statements'] = array();
        }
    }
    
    public function getConnector()
    {
        $connection = $this->getConnectionInfo();
        return \Amiss\Sql\Connector::create($connection);
    }
    
    public function getConnectionInfo()
    {
        return TestApp::instance()->getConnectionInfo();
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

class TestApp
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
    
class ModelDataTestCase extends DataTestCase
{
    /**
     * @var Amiss\Sql\Manager
     */
    public $manager;
    
    public function getMapper()
    {
        $mapper = \Amiss::createSqlMapper(array(
            'dbTimeZone'=>'UTC',
        ));
        $mapper->objectNamespace = 'Amiss\Demo';
        return $mapper;
    }
    
    public function getManager()
    {
        return \Amiss::createSqlManager($this->db, $this->mapper);
    }
    
    public function setUp()
    {
        parent::setUp();
        
        \Amiss\Sql\ActiveRecord::_reset();
        
        $this->db = $this->getConnector();
        $this->db->exec($this->readSqlFile(__DIR__.'/../doc/demo/schema.{engine}.sql'));
        $this->db->exec($this->readSqlFile(__DIR__.'/../doc/demo/testdata.sql'));
        
        $this->mapper = $this->getMapper();
        $this->manager = $this->getManager();
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
    }
}

class ActiveRecordDataTestCase extends ModelDataTestCase
{
    public function getMapper()
    {
        $mapper = parent::getMapper();
        $mapper->objectNamespace = 'Amiss\Demo\Active';
        return $mapper;
    }
}

class LooseStringMatch extends PHPUnit_Framework_Constraint
{
    /**
     * @var string
     */
    protected $string;

    /**
     * @param string $pattern
     */
    public function __construct($string)
    {
		parent::__construct($string);
        $this->string = $string;
    }

    /**
     * Evaluates the constraint for parameter $other. Returns TRUE if the
     * constraint is met, FALSE otherwise.
     *
     * @param mixed $other Value or object to evaluate.
     * @return bool
     */
    public function evaluate($other, $description = '', $returnResult = FALSE)
    {
        $result = false;
        if ($this->string) {
            $pattern = '/'.preg_replace('/\s+/', '\s*', preg_quote($this->string, '/')).'/ix';
            $result = preg_match($pattern, $other) > 0;
        }
        if (!$returnResult) {
            if (!$result) $this->fail($other, $description);
        }
        else
            return $result;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        return sprintf('matches loose string "%s"', $this->string);
    }
}

class DatabaseSuite extends PHPUnit_Framework_TestSuite
{
    public $connectionInfo;

    public function __construct($conn)
    {
        if (!is_array($conn))
            throw new \InvalidArgumentException();

        $this->connectionInfo = $conn;
    }

    public function setUp()
    {
        TestApp::instance()->connectionInfo = $this->connectionInfo;
        parent::setUp();
    }
}

