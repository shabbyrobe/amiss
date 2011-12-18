<?php

namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

class ActiveRecordTypeTest extends \SqliteDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		$this->manager->objectNamespace = 'Amiss\Demo\Active';
		\Amiss\Active\Record::_reset();
		\Amiss\Active\Record::setManager($this->manager);
	}
	
	public function testClassSpecificTypeConverterOnRetrieve()
	{
		\Amiss\Demo\Active\EventRecord::addTypeHandler(new TestTypeHandler(), 'datetime');
		$event = \Amiss\Demo\Active\EventRecord::getByPk(1);
		$this->assertEquals('z1936-01-01z', $event->dateStart);
		$this->assertEquals('z1936-01-02z', $event->dateEnd);
	}
	
	public function testClassSpecificTypeConverterOnSave()
	{
		\Amiss\Demo\Active\EventRecord::addTypeHandler(new TestTypeHandler(), 'datetime');
		$event = \Amiss\Demo\Active\EventRecord::getByPk(1);
		
		$event->save();
		$event = \Amiss\Demo\Active\EventRecord::getByPk(1);
		$this->assertEquals('zz2001-01-01 15:15:15zz', $event->dateStart);
		$this->assertEquals('zz2001-01-01 15:15:15zz', $event->dateEnd);
	}
	
	public function testGlobalTypeConverterOnRetrieve()
	{
		\Amiss\Active\Record::addTypeHandler(new TestTypeHandler(), 'datetime');
		$event = \Amiss\Demo\Active\EventRecord::getByPk(1);
		$this->assertEquals('z1936-01-01z', $event->dateStart);
		$this->assertEquals('z1936-01-02z', $event->dateEnd);
	}
	
	public function testGlobalTypeConverterOnSave()
	{
		\Amiss\Active\Record::addTypeHandler(new TestTypeHandler(), 'datetime');
		$event = \Amiss\Demo\Active\EventRecord::getByPk(1);
		
		$event->save();
		$event = \Amiss\Demo\Active\EventRecord::getByPk(1);
		$this->assertEquals('zz2001-01-01 15:15:15zz', $event->dateStart);
		$this->assertEquals('zz2001-01-01 15:15:15zz', $event->dateEnd);
	}

	public function testClassSpecificTypeConverterOverridesGlobalOnRetrieve()
	{
		\Amiss\Demo\Active\EventRecord::addTypeHandler(new TestTypeHandler('a'), array('datetime', 'foo'));
		\Amiss\Active\Record::addTypeHandler(new TestTypeHandler('b'), 'datetime');
		
		$event = \Amiss\Demo\Active\EventRecord::getByPk(1);
		$this->assertEquals('a1936-01-01a', $event->dateStart);
		$this->assertEquals('a1936-01-02a', $event->dateEnd);
	}
	
	/**
	 * In earlier tests, different orders seemed to produce different results. This
	 * helps make sure that doesn't return.
	 */
	public function testClassSpecificTypeConverterOverridesGlobalOnRetrieveWhenAssignedInADifferentOrder()
	{
		\Amiss\Active\Record::addTypeHandler(new TestTypeHandler('b'), 'datetime');
		\Amiss\Demo\Active\EventRecord::addTypeHandler(new TestTypeHandler('a'), 'datetime');
		\Amiss\Demo\Active\EventRecord::addTypeHandler(new TestTypeHandler('a'), 'foo');
		
		$event = \Amiss\Demo\Active\EventRecord::getByPk(1);
		$this->assertEquals('a1936-01-01a', $event->dateStart);
		$this->assertEquals('a1936-01-02a', $event->dateEnd);
	}
	
	public function testCustomType()
	{
		$this->createRecordMemoryDb(__NAMESPACE__.'\TestCustomFieldTypeRecord');
		TestCustomFieldTypeRecord::addTypeHandler(new TestCustomFieldTypeHandler(), 'foo');
		
		$r = new TestCustomFieldTypeRecord;
		$r->yep1 = 'foo';
		$r->save();
		
		$r = TestCustomFieldTypeRecord::getByPk(1);
		
		// this will have passed through the prepareValueForDb first, then
		// through the handleValueFromDb method
		$this->assertEquals('value-db-foo', $r->yep1);
	}
}

class TestTypeHandler implements \Amiss\Active\TypeHandler
{
	public $garbage;
	
	public function __construct($garbage='z')
	{
		$this->garbage = $garbage;
	}
	
	public function prepareValueForDb($value, $object, $fieldName)
	{
		return $this->garbage.'2001-01-01 15:15:15'.$this->garbage;
	}
	
	public function handleValueFromDb($value, $object, $fieldName)
	{
		return $this->garbage.$value.$this->garbage;
	}
	
	function createColumnType($engine)
	{}
}

class TestCustomFieldTypeRecord extends \Amiss\Active\Record
{
	public static $fields = array(
		'yep1'=>'foo bar',
	);
	
	public $testCustomFieldTypeRecordId;
	public $yep1;
}

class TestCustomFieldTypeHandler implements \Amiss\Active\TypeHandler
{
	function prepareValueForDb($value, $object, $fieldName)
	{
		return "db-$value";
	}
	
	function handleValueFromDb($value, $object, $fieldName)
	{
		return "value-$value"; 
	}
	
	function createColumnType($engine)
	{}
}
