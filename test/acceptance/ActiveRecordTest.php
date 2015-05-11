<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo\Active;

/**
 * @group active
 * @group acceptance
 */
class ActiveRecordTest extends \ActiveRecordDataTestCase
{
    public function setUp()
    {
        parent::setUp();
        \Amiss\Sql\ActiveRecord::_reset();
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
    }

    public function testGetById()
    {
        $obj = Active\ArtistRecord::getById(1);
        $this->assertTrue($obj instanceof Active\ArtistRecord);
        $this->assertEquals(1, $obj->artistId);
    }

    public function testGetByPositionalWhere()
    {
        $obj = Active\ArtistRecord::get('artistId=?', [1]);
        $this->assertTrue($obj instanceof Active\ArtistRecord);
        $this->assertEquals(1, $obj->artistId);
    }

    public function testGetByPositionalWhereMulti()
    {
        $obj = Active\ArtistRecord::get('artistId=? AND artistTypeId=?', [1, 1]);
        $this->assertTrue($obj instanceof Active\ArtistRecord);
        $this->assertEquals(1, $obj->artistId);
    }

    public function testGetByNamedWhere()
    {
        $obj = Active\ArtistRecord::get('artistId=:id', array(':id'=>1));
        $this->assertTrue($obj instanceof Active\ArtistRecord);
        $this->assertEquals(1, $obj->artistId);
    }
    
    public function testGetRelatedSingle()
    {
        $obj = Active\ArtistRecord::getById(1);
        $this->assertTrue($obj==true, "Couldn't retrieve object");

        $related = $obj->getRelated('type');

        $this->assertTrue($related instanceof Active\ArtistType);
        $this->assertEquals(1, $related->artistTypeId);
    }
    
    public function testGetRelatedWithLazyLoad()
    {
        $obj = Active\ArtistRecord::getById(1);
        $this->assertTrue($obj==true, "Couldn't retrieve object");
        
        $this->assertNull($this->getProtected($obj, 'type'));
        $type = $obj->getType();
        $this->assertTrue($this->getProtected($obj, 'type') instanceof Active\ArtistType);
    }

    public function testDeleteByPrimary()
    {
        $obj = Active\ArtistRecord::getById(1);
        $this->assertTrue($obj==true, "Couldn't retrieve object");

        $obj->delete();
        $this->assertEquals(0, $this->manager->count('ArtistRecord', 'artistId=1'));

        // sanity check: make sure we didn't delete everything!
        $this->assertGreaterThan(0, $this->manager->count('ArtistRecord'));
    }

    public function testDeleteById()
    {
        $obj = Active\ArtistRecord::getById(1);
        $this->assertTrue($obj==true, "Couldn't retrieve object");
        Active\ArtistRecord::deleteById(1);

        $this->assertEquals(0, $this->manager->count('ArtistRecord', 'artistId=1'));

        // sanity check: make sure we didn't delete everything!
        $this->assertGreaterThan(0, $this->manager->count('ArtistRecord'));
    }

    public function testUpdateByPrimary()
    {
        $n = md5(uniqid('', true));
        $obj = Active\ArtistRecord::getById(1);
        $obj->name = $n;
        $obj->update();

        $obj = Active\ArtistRecord::getById(1);
        $this->assertEquals($n, $obj->name);
    }

    public function testInsert()
    {
        $n = md5(uniqid('', true));

        $obj = new Active\ArtistRecord;
        $this->assertNull($obj->artistId);
        $obj->artistTypeId = 1;
        $obj->name = $n;
        $obj->slug = $n;
        $obj->insert();

        $this->assertGreaterThan(0, $obj->artistId);
        $obj = Active\ArtistRecord::getById($obj->artistId);
        $this->assertEquals($obj->name, $n);
    }

    public function testSaveUpdate()
    {
        $n = md5(uniqid('', true));
        $obj = Active\ArtistRecord::getById(1);
        $obj->name = $n;
        $obj->save();

        $obj = Active\ArtistRecord::getById(1);
        $this->assertEquals($n, $obj->name);
    }

    public function testSaveInsert()
    {
        $n = md5(uniqid('', true));

        $obj = new Active\ArtistRecord;
        $this->assertNull($obj->artistId);
        $obj->artistTypeId = 1;
        $obj->name = $n;
        $obj->slug = $n;
        $obj->save();

        $this->assertGreaterThan(0, $obj->artistId);
        $obj = Active\ArtistRecord::getById($obj->artistId);
        $this->assertEquals($obj->name, $n);
    }
}
