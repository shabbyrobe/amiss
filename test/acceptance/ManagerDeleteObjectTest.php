<?php

namespace Amiss\Test\Acceptance;

class ManagerDeleteObjectTest extends \NoteMapperDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		
		$this->artist = $this->manager->get('Artist', 'artistId=?', 1);
		if (!$this->artist)
			throw new \UnexpectedValueException("Unexpected test data");
	}
	
	/**
	 * Ensures the signature for the 'autoincrement primary key' update method works
	 *   Amiss\Manager->delete( object $object )
	 * 
	 * @group acceptance
	 * @group manager
	 */
	public function testDeleteObjectByAutoincrementPrimaryKey()
	{
		$this->manager->delete($this->artist);
		$this->assertEquals(0, $this->manager->count('Artist', 'name="Foobar"'));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertGreaterThan(0, $this->manager->count('Artist'));
	}
	
	/**
	 * Ensures the signature for the 'autoincrement primary key' update method works
	 *   Amiss\Manager->delete( object $object )
	 * 
	 * @group acceptance
	 * @group manager
	 * @expectedException Amiss\Exception
	 */
	public function testDeleteObjectWithoutAutoincrementPrimaryKeyFails()
	{
		$mapper = new \TestMapper(array(
			'Amiss\Demo\Artist'=>new \Amiss\Meta('Artist', 'artist', array()),
		));
		
		$manager = new \Amiss\Manager($this->manager->connector, $mapper);
		$manager->delete($this->artist);
	}
}
