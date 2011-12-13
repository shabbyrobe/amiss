<?php

/*
 * This file is part of Amiss.
 * 
 * Amiss is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * Amiss is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public License
 * along with Amiss.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * Copyright 2011 Blake Williams
 * http://k3jw.com 
 */

namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

class ActiveRecordTest extends \SqliteDataTestCase
{
	public function setUp()
	{
		parent::setUp();
		$this->manager->objectNamespace = 'Amiss\Demo\Active';
		\Amiss\Active\Record::_reset();
		\Amiss\Active\Record::setManager($this->manager);
	}
	
	public function testGetByPk()
	{
		$obj = Active\ArtistRecord::getByPk(1);
		$this->assertTrue($obj instanceof Active\ArtistRecord);
		$this->assertEquals(1, $obj->artistId);
	}
	
	public function testGetByPositionalWhere()
	{
		$obj = Active\ArtistRecord::get('artistId=?', 1);
		$this->assertTrue($obj instanceof Active\ArtistRecord);
		$this->assertEquals(1, $obj->artistId);
	}
	
	public function testGetByPositionalWhereMulti()
	{
		$obj = Active\ArtistRecord::get('artistId=? AND artistTypeId=?', 1, 1);
		$this->assertTrue($obj instanceof Active\ArtistRecord);
		$this->assertEquals(1, $obj->artistId);
	}
	
	public function testGetByNamedWhere()
	{
		$obj = Active\ArtistRecord::get('artistId=:id', array(':id'=>1));
		$this->assertTrue($obj instanceof Active\ArtistRecord);
		$this->assertEquals(1, $obj->artistId);
	}
	
	public function testGetPopulatesUndeclaredProperties()
	{
		$obj = Active\VenueRecord::get('venueId=:id', array(':id'=>1));
		$this->assertTrue($obj instanceof Active\VenueRecord);
		$this->assertEquals(1, $obj->venueId);
		$this->assertEquals('31.1234', $obj->latitude);
		$this->assertEquals('124.4444', $obj->longitude);
	}
	
	public function testGetRelatedSingle()
	{
		$obj = Active\ArtistRecord::getByPk(1);
		$related = $obj->fetchRelated('type');
		
		$this->assertTrue($related instanceof Active\ArtistType);
		$this->assertEquals(1, $related->artistTypeId);
	}
	
	public function testDeleteByPrimary()
	{
		$obj = Active\ArtistRecord::getByPk(1);
		$obj->delete();
		$this->assertEquals(0, $this->manager->count('Artist', 'artistId=1'));
		
		// sanity check: make sure we didn't delete everything!
		$this->assertGreaterThan(0, $this->manager->count('Artist'));
	}
	
	public function testUpdateByPrimary()
	{
		$n = uniqid('', true);
		$obj = Active\ArtistRecord::getByPk(1);
		$obj->name = $n;
		$obj->update();
		
		$obj = Active\ArtistRecord::getByPk(1);
		$this->assertEquals($n, $obj->name);
	}
	
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
	
	public function testSaveUpdate()
	{
		$n = uniqid('', true);
		$obj = Active\ArtistRecord::getByPk(1);
		$obj->name = $n;
		$obj->save();
		
		$obj = Active\ArtistRecord::getByPk(1);
		$this->assertEquals($n, $obj->name);
	}
	
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
