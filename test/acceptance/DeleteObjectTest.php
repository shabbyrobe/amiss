<?php

namespace Amiss\Test\Acceptance;

class DeleteObjectTest extends \SqliteDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		
		$this->artist = $this->manager->get('Artist', 'artistId=?', 1);
		$this->assertEquals('Limozeen', $this->artist->name);
	}
	
	/**
	 * Ensures the signature for the 'autoincrement primary key' update method works
	 *   Amiss\Manager->delete( object $object , string $pkField )
	 * 
	 */
	public function testDeleteObjectByAutoincrementPrimaryKey()
	{
		$this->manager->delete($this->artist, 'artistId');
		$this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertGreaterThan(0, $this->manager->count('Artist'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->delete( object $object , string $positionalWhere , [ string $param1, ... ] )
	 * 
	 */
	public function testDeleteObjectWithPositionalWhereAndSingleParameter()
	{
		$this->manager->delete($this->artist, 'artistId=?', 1);
		$this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertGreaterThan(0, $this->manager->count('Artist'));
	}
	
	/**
	 * Ensures the following signature works as expected:
	 *   Amiss\Manager->delete( object $object , string $namedWhere , array $params )
	 * 
	 */
	public function testDeleteObjectWithNamedWhereAndSingleParameter()
	{
		$this->manager->delete($this->artist, 'artistId=:id', array('id'=>1));
		$this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertGreaterThan(0, $this->manager->count('Artist'));
	}
}
