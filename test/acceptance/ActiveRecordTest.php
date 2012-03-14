<?php

namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

class ActiveRecordTest extends \ActiveRecordDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		\Amiss\Active\Record::_reset();
		\Amiss\Active\Record::setManager($this->manager);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testGetByPk()
	{
		$obj = Active\ArtistRecord::getByPk(1);
		$this->assertTrue($obj instanceof Active\ArtistRecord);
		$this->assertEquals(1, $obj->artistId);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testGetByPositionalWhere()
	{
		$obj = Active\ArtistRecord::get('artistId=?', 1);
		$this->assertTrue($obj instanceof Active\ArtistRecord);
		$this->assertEquals(1, $obj->artistId);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testGetByPositionalWhereMulti()
	{
		$obj = Active\ArtistRecord::get('artistId=? AND artistTypeId=?', 1, 1);
		$this->assertTrue($obj instanceof Active\ArtistRecord);
		$this->assertEquals(1, $obj->artistId);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testGetByNamedWhere()
	{
		$obj = Active\ArtistRecord::get('artistId=:id', array(':id'=>1));
		$this->assertTrue($obj instanceof Active\ArtistRecord);
		$this->assertEquals(1, $obj->artistId);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testGetPopulatesUndeclaredProperties()
	{
		$obj = Active\VenueRecord::get('venueId=:id', array(':id'=>1));
		$this->assertTrue($obj instanceof Active\VenueRecord);
		$this->assertEquals(1, $obj->venueId);
		$this->assertEquals('31.1234', $obj->latitude);
		$this->assertEquals('124.4444', $obj->longitude);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testGetRelatedSingle()
	{
		$obj = Active\ArtistRecord::getByPk(1);
		$this->assertTrue($obj==true, "Couldn't retrieve object");
		
		$related = $obj->fetchRelated('type');
		
		$this->assertTrue($related instanceof Active\ArtistType);
		$this->assertEquals(1, $related->artistTypeId);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testDeleteByPrimary()
	{
		$obj = Active\ArtistRecord::getByPk(1);
		$this->assertTrue($obj==true, "Couldn't retrieve object");
		
		$obj->delete();
		$this->assertEquals(0, $this->manager->count('ArtistRecord', 'artistId=1'));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertGreaterThan(0, $this->manager->count('ArtistRecord'));
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testUpdateByPrimary()
	{
		$n = uniqid('', true);
		$obj = Active\ArtistRecord::getByPk(1);
		$obj->name = $n;
		$obj->update();
		
		$obj = Active\ArtistRecord::getByPk(1);
		$this->assertEquals($n, $obj->name);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testInsert()
	{
		$n = uniqid('', true);
		
		$obj = new Active\ArtistRecord;
		$this->assertNull($obj->artistId);
		$obj->artistTypeId = 1;
		$obj->name = $n;
		$obj->slug = $n;
		$obj->insert();
		
		$this->assertGreaterThan(0, $obj->artistId);
		$obj = Active\ArtistRecord::getByPk($obj->artistId);
		$this->assertEquals($obj->name, $n);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testSaveUpdate()
	{
		$n = uniqid('', true);
		$obj = Active\ArtistRecord::getByPk(1);
		$obj->name = $n;
		$obj->save();
		
		$obj = Active\ArtistRecord::getByPk(1);
		$this->assertEquals($n, $obj->name);
	}
	
	/**
	 * @group active
	 * @group acceptance
	 */
	public function testSaveInsert()
	{
		$n = uniqid('', true);
		
		$obj = new Active\ArtistRecord;
		$this->assertNull($obj->artistId);
		$obj->artistTypeId = 1;
		$obj->name = $n;
		$obj->slug = $n;
		$obj->save();
		
		$this->assertGreaterThan(0, $obj->artistId);
		$obj = Active\ArtistRecord::getByPk($obj->artistId);
		$this->assertEquals($obj->name, $n);
	}
}
