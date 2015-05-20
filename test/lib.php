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
            TestApp::instance()->connectionInfo['statements'] = array(
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
            TestApp::instance()->connectionInfo['statements'] = array();
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

class CustomMapperTestCase extends DataTestCase
{
    private static $classes = [];
    private $tearDownStatements = [];

    protected function createClasses($classes)
    {
        $classes = (array)$classes;

        $hash = '';
        foreach ($classes as $k=>$v) {
            $hash .= "$k|$v|";
        }
        $classHash = sha1($hash);
        if (isset(self::$classes[$classHash])) {
            list ($ns, $classes) = self::$classes[$classHash];
        }
        else {
            $ns = "AmissTest_".$classHash;
            $script = "namespace $ns;";
            foreach ($classes as $k=>$v) {
                $script .= $v;
            }
            $classes = get_declared_classes();
            eval($script);
            $classes = array_values(array_diff(get_declared_classes(), $classes));
            self::$classes[$classHash] = [$ns, $classes];
        }
        return [$classHash, $ns, $classes];
    }

    public function createDefaultNoteManager($classes)
    {
        $ns = null;
        $classNames = [];
        if ($classes) {
            list ($classHash, $ns, $classNames) = $this->createClasses($classes);
        }
        $manager = $this->createDefaultManager();
        if ($ns) {
            $manager->mapper->objectNamespace = $ns;
        }
        $this->prepareManager($manager, $classNames);
        return [$manager, $ns];
    }

    public function createDefaultArrayManager($map)
    {
        $mapper = new \Amiss\Mapper\Arrays($map);
        $config = [
            'dbTimeZone'=>'UTC',
        ];
        $mapper->typeHandlers = \Amiss\Sql\Factory::createTypeHandlers($config);
        $manager = $this->createDefaultManager($mapper);
        $this->prepareManager($manager, array_keys($map));
        return $manager;
    }

    protected function createDefaultManager($mapper=null)
    {
        $connector = $this->getConnector();
        $info = $this->getConnectionInfo();
        $manager = \Amiss\Sql\Factory::createManager($connector, array(
            'dbTimeZone'=>'UTC',
            'mapper'=>$mapper,
        ));
        return $manager;
    }

    protected function prepareManager($manager, $classNames)
    {
        switch ($manager->connector->engine) {
        case 'mysql':
            $connector->exec("DROP DATABASE IF EXISTS `{$info['dbName']}`");
            $connector->exec("CREATE DATABASE `{$info['dbName']}`");
            $connector->exec("USE `{$info['dbName']}`");
        break;
        case 'sqlite': break;
        default:
            throw new \Exception();
        }
        if ($classNames) {
            \Amiss\Sql\TableBuilder::create($manager->connector, $manager->mapper, $classNames);
        }
        return $manager;
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
        $mapper = \Amiss\Sql\Factory::createMapper(array(
            'dbTimeZone'=>'UTC',
        ));
        $mapper->objectNamespace = 'Amiss\Demo';
        return $mapper;
    }
    
    public function getManager()
    {
        return \Amiss\Sql\Factory::createManager($this->db, $this->mapper);
    }
    
    public function setUp()
    {
        parent::setUp();
        
        \Amiss\Sql\ActiveRecord::_reset();
        
        $this->db = $this->getConnector();
        $this->db->exec($this->readSqlFile(__DIR__.'/../doc/demo/schema.{engine}.sql'));
        $this->db->exec($this->readSqlFile(__DIR__.'/../doc/demo/testdata.sql'));

        $this->db->queries = 0;
        
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
