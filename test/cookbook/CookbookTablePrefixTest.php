<?php

namespace Amiss\Test\Cookbook;

class CookbookTablePrefixTest extends \CustomTestCase
{
	public function setUp()
	{
		$mapper = $this->mapper = new \Amiss\Mapper\Note();
	    
	    $translator = new \Amiss\Name\CamelToUnderscore();
		
	    $this->mapper->defaultTableNameTranslator = function($objectName) use ($mapper, $translator) {
	        return 'yep_'.$mapper->convertUnknownTableName($objectName);
	    };
	    $this->mapper->objectNamespace = __NAMESPACE__;
	    
	    $this->manager = new \Amiss\Manager(array(), $this->mapper);
	}
	
	/**
	 * @group cookbook
	 */
	public function testRetrieve()
	{
		$meta = $this->manager->getMeta('CookbookTablePrefixObject');
		$this->assertEquals('yep_cookbook_table_prefix_object', $meta->table);
	}
}

class CookbookTablePrefixObject
{
	/**
	 * @primary
	 * @type autoinc
	 */
	public $id;
	
	/**
	 * @field thing_part1
	 * @type pants
	 */
	public $thing;
}
