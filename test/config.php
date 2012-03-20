<?php

use Amiss\TableBuilder;

require_once(__DIR__.'/../src/Loader.php');

date_default_timezone_set('Australia/Melbourne');

spl_autoload_register(array(new Amiss\Loader, 'load'));

require_once(__DIR__.'/../doc/demo/model.php');
require_once(__DIR__.'/../doc/demo/ar.php');

abstract class CustomTestCase extends PHPUnit_Framework_TestCase
{
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
}

abstract class SqliteDataTestCase extends CustomTestCase
{
	/**
	 * @var Amiss\Manager
	 */
	public $manager;
	
	public abstract function getMapper();
	
	public function setUp()
	{
		\Amiss\Active\Record::_reset();
		
		$this->db = new \Amiss\Connector('sqlite::memory:', null, null, array(\PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION));
		$this->db->exec(file_get_contents(__DIR__.'/../doc/demo/schema.sqlite'));
		$this->db->exec(file_get_contents(__DIR__.'/../doc/demo/testdata.sqlite'));
		
		$this->mapper = $this->getMapper();
		$this->manager = new \Amiss\Manager($this->db, $this->mapper);
		\Amiss\Active\Record::setManager($this->manager);
	}
	
	public function createRecordMemoryDb($class)
	{
		$tb = new TableBuilder($this->manager, $class);
		forward_static_call(array($class, 'setManager'), $this->manager);
		$tb->createTable();
	}
}

abstract class ActiveRecordDataTestCase extends SqliteDataTestCase
{
	public function getMapper()
	{
		$mapper = new \Amiss\Mapper\Statics();
		$mapper->objectNamespace = 'Amiss\Demo\Active';
		return $mapper;
	}
}

abstract class NoteMapperDataTestCase extends SqliteDataTestCase
{
	public function getMapper()
	{
		$mapper = new \Amiss\Mapper\Note();
		$mapper->objectNamespace = 'Amiss\Demo';
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
        return sprintf(
          'matches loose string "%s"',

          $this->string
        );
    }
}

class TestConnector extends \Amiss\Connector
{
	public $calls = array();
	
	public function exec($statement)
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

class TestStatement
{
	public $queryString;
	public $params = array();
	public $driverOptions = array();
	public $result;
	
	public function __construct($connector, $statement, $driverOptions)
	{
		$this->connector = $connector;
		$this->driverOptions = $driverOptions;
		$this->queryString = $statement;
	}
	
	public function execute()
	{
		$this->connector->calls[] = array($this->queryString, $this->params);
		$this->params = array();
	}
	
	public function fetchColumn()
	{
		$result = $this->result;
		$this->result = null;
		return $result;
	}
}

class TestMapper implements \Amiss\Mapper
{
	public $meta;
	
	function __construct($meta=array())
	{
		$this->meta = $meta;
	}
	
	function getMeta($class)
	{
		return isset($this->meta[$class]) ? $this->meta[$class] : null;
	}
	
	function createObject($meta, $row, $args) {}
	
	function populateObject($meta, $object, $row) {}

	function exportRow($meta, $object) {}
	
	function determineTypeHandler($type) {}
}

class TestTypeHandler implements \Amiss\Type\Handler
{
	public $valueForDb;
	public $valueFromDb;
	public $columnType;
	
	public function __construct($data=array())
	{
		foreach ($data as $k=>$v) $this->$k = $v;
	}
	
	function prepareValueForDb($value, $object, $fieldName)
	{
		return $this->valueForDb;
	}
	
	function handleValueFromDb($value, $object, $fieldName)
	{
		return $this->valueFromDb;
	}
	
	function createColumnType($engine)
	{
		return $this->columnType;
	}
}
