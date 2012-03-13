<?php

namespace Amiss\Test\Acceptance;

use Amiss\Criteria\Query;

class DeleteFromTableTest extends \NoteMapperDataTestCase
{
	public function setUp()
	{
		parent::setUp();	
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->delete( string $table, string $positionalWhere, [ $param1, ... ] )
	 * 
	 */
	public function testDeleteTableWithArraySetAndPositionalWhere()
	{
		$this->assertEquals(8, $this->manager->count('Artist', 'artistTypeId=?', 1));
		
		$this->manager->delete('Artist', 'artistTypeId=?', 1);
		
		$this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertEquals(3, $this->manager->count('Artist'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->delete( string $table, string $namedWhere, array $params )
	 * 
	 */
	public function testDeleteTableWithArraySetAndNamedWhere()
	{
		$this->assertEquals(8, $this->manager->count('Artist', 'artistTypeId=?', 1));
		
		$this->manager->delete('Artist', 'artistTypeId=:id', array(':id'=>1));
		
		$this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertEquals(3, $this->manager->count('Artist'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->delete( string $table, array $criteria )
	 * 
	 */
	public function testDeleteTableWithArrayCriteria()
	{
		$this->assertEquals(8, $this->manager->count('Artist', 'artistTypeId=?', 1));
		
		$this->manager->delete('Artist', array('where'=>'artistTypeId=:id', 'params'=>array(':id'=>1)));
		
		$this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));

		// sanity check: make sure we didn't delete everything!
		$this->assertEquals(3, $this->manager->count('Artist'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->delete( string $table, Criteria\Query $criteria )
	 * 
	 */
	public function testDeleteTableWithObjectCriteria()
	{
		$this->assertEquals(8, $this->manager->count('Artist', 'artistTypeId=?', 1));
		
		$criteria = new Query(array('where'=>'artistTypeId=:id', 'params'=>array(':id'=>1)));
		$this->manager->delete('Artist', $criteria);
		
		$this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertEquals(3, $this->manager->count('Artist'));
	}
}
