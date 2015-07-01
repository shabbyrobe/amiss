<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;

/**
 * @group acceptance
 * @group manager 
 */
class ManagerSelectTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        $this->deps = Test\Factory::managerModelDemo();
        $this->manager = $this->deps->manager;
    }

    public function tearDown()
    {
        $this->manager = null;
        $this->deps = null;
        parent::tearDown();
    }

    public function testExists()
    {
        $a = $this->manager->exists('Artist', 1);
        $this->assertTrue($a);
    }

    public function testExistsFalse()
    {
        $a = $this->manager->exists('Artist', PHP_INT_MAX);
        $this->assertFalse($a);
    }
    
    public function testSingleObjectPositionalParametersShorthand()
    {
        $a = $this->manager->get('Artist', 'slug=?', ['limozeen']);
        $this->assertTrue($a instanceof \Amiss\Demo\Artist);
        $this->assertEquals('Limozeen', $a->name);
    }
    
    public function testSingleObjectNamedParametersShorthand()
    {
        $a = $this->manager->get('Artist', 'slug=:slug', array(':slug'=>'limozeen'));
        $this->assertTrue($a instanceof \Amiss\Demo\Artist);
        $this->assertEquals('Limozeen', $a->name);
    }
    
    public function testSingleObjectNamedParametersLongForm()
    {
        $a = $this->manager->get(
            'Artist', 
            array(
                'where'=>'slug=:slug', 
                'params'=>array(':slug'=>'limozeen')
            )
        );
        $this->assertTrue($a instanceof \Amiss\Demo\Artist);
        $this->assertEquals('Limozeen', $a->name);
    }
    
    public function testSingleObjectUsingQuery()
    {
        $criteria = new \Amiss\Sql\Query\Select;
        $criteria->where = 'slug=:slug';
        $criteria->params[':slug'] = 'limozeen';
        
        $a = $this->manager->get('Artist', $criteria);
        
        $this->assertTrue($a instanceof \Amiss\Demo\Artist);
        $this->assertEquals('Limozeen', $a->name);
    }
    
    public function testList()
    {
        $artists = $this->manager->getList('Artist');
        $this->assertTrue(is_array($artists));
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('limozeen', current($artists)->slug);
        next($artists);
        $this->assertEquals('taranchula', current($artists)->slug);
    }
    
    public function testListByProperty()
    {
        $artists = $this->manager->getList('Artist', ['where'=>['artistTypeId'=>2]]);
        $this->assertTrue(is_array($artists));
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('george-carlin', current($artists)->slug);
    }

    public function testListByPropertyIn()
    {
        $artists = $this->manager->getList('Artist', ['where'=>['artistId'=>[1, 2]]]);
        $this->assertTrue(is_array($artists));
        $this->assertCount(2, $artists);
        $this->assertEquals(1, $artists[0]->artistId);
        $this->assertEquals(2, $artists[1]->artistId);
    }

    /**
     * Select using an array where clause, but using values that get mapped through
     * a type handler.
     */
    public function testListByTypePropertyIn()
    {
        $deps = Test\Factory::managerArraysModelCustom([
            'Pants'=>[
                'class'   => 'stdClass',
                'table'   => 't1',
                'primary' => 'id',
                'fields'  => ['id'=>['type'=>'autoinc'], 'd'=>['type'=>'date']],
            ],
        ]);
        $d1 = new \DateTime('2015-01-01', new \DateTimeZone('UTC'));
        $d2 = new \DateTime('2015-01-02', new \DateTimeZone('UTC'));
        $deps->manager->insertTable('Pants', ['d'=>$d1]);
        $deps->manager->insertTable('Pants', ['d'=>$d2]);
        $deps->manager->insertTable('Pants', ['d'=>new \DateTime('2015-01-03', new \DateTimeZone('UTC'))]);
        $result = $deps->manager->getList('Pants', ['where'=>['d'=>[$d1, $d2]]]);
        $this->assertEquals($d1, $result[0]->d);
        $this->assertEquals($d2, $result[1]->d);
    }

    public function testPagedListFirstPage()
    {
        $artists = $this->manager->getList('Artist', array('page'=>array(1, 3)));
        $this->assertEquals(3, count($artists));
        
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('limozeen', current($artists)->slug);
        next($artists);
        $this->assertEquals('taranchula', current($artists)->slug);
    }

    public function testPagedListSecondPage()
    {
        $artists = $this->manager->getList('Artist', array('page'=>array(2, 3)));
        $this->assertEquals(3, count($artists));
        
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('george-carlin', current($artists)->slug);
        next($artists);
        $this->assertEquals('david-cross', current($artists)->slug);
    }

    public function testListLimit()
    {
        $artists = $this->manager->getList('Artist', array('limit'=>3));
        $this->assertEquals(3, count($artists));
        
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('limozeen', current($artists)->slug);
        next($artists);
        $this->assertEquals('taranchula', current($artists)->slug);
    }
    
    public function testListOffset()
    {
        $artists = $this->manager->getList('Artist', array('limit'=>3, 'offset'=>3));
        $this->assertEquals(3, count($artists));
        
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('george-carlin', current($artists)->slug);
        next($artists);
        $this->assertEquals('david-cross', current($artists)->slug);
    }
    
    public function testOrderByManualImpliedAsc()
    {
        $artists = $this->manager->getList('Artist', array('order'=>'name'));
        $this->assertTrue(is_array($artists));
        $this->assertEquals('anvil', current($artists)->slug);
        foreach ($artists as $a); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals('the-sonic-manipulator', $a->slug);
    }
    
    public function testOrderByManualDesc()
    {
        $artists = $this->manager->getList('Artist', array('order'=>'name desc'));
        $this->assertTrue(is_array($artists));
        $this->assertEquals('the-sonic-manipulator', current($artists)->slug);
        foreach ($artists as $a); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals('anvil', $a->slug);
    }
    
    public function testOrderByManualMulti()
    {
        $eventArtists = $this->manager->getList('EventArtist', array(
            'limit'=>3, 
            'where'=>'eventId=1',
            'order'=>'priority, sequence desc',
        ));
        
        $this->assertTrue(is_array($eventArtists));
        
        $result = array();
        foreach ($eventArtists as $ea) {
            $result[] = array($ea->priority, $ea->sequence);
        }
        
        $this->assertEquals(array(
            array(1, 2),
            array(1, 1),
            array(2, 1),
        ), $result);
    }
    
    public function testOrderBySingleLongForm()
    {
        $artists = $this->manager->getList('Artist', array('order'=>array('name')));
        $this->assertEquals('anvil', current($artists)->slug);
        $this->assertTrue(is_array($artists));
        foreach ($artists as $a); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals('the-sonic-manipulator', $a->slug);
    }

    public function testOrderBySingleLongFormDescending()
    {
        $artists = $this->manager->getList('Artist', array('order'=>array('name'=>'desc')));
        $this->assertTrue(is_array($artists));
        
        $this->assertEquals('the-sonic-manipulator', current($artists)->slug);
        foreach ($artists as $a); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals('anvil', $a->slug);
    }
    
    public function testOrderByGetterProperty()
    {
        $events = $this->manager->getList('Event', array('order'=>array('subName')));
        $this->assertTrue(is_array($events));
        
        $this->assertEquals(2, current($events)->eventId);
        foreach ($events as $e); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals(1, $e->eventId);
    }
    
    public function testSelectSingleObjectFromMultipleResultWhenLimitIsOne()
    {
        $artist = $this->manager->get('Artist', array('order'=>array('name'=>'desc'), 'limit'=>1));
        $this->assertTrue($artist instanceof \Amiss\Demo\Artist);
        
        $this->assertEquals('the-sonic-manipulator', $artist->slug);
    }
    
    /**
     * @expectedException Amiss\Exception
     */
    public function testSelectSingleObjectFailsWhenResultReturnsMany()
    {
        $artist = $this->manager->get('Artist', array('order'=>array('name'=>'desc')));
    }
    
    /**
     * @expectedException Amiss\Exception
     */
    public function testSelectSingleObjectFailsWithoutIssuingQueryWhenLimitSetButNotOne()
    {
        $this->manager->connector = $this->getMock('PDOK\Connector', array('prepare'), array(''));
        $this->manager->connector->expects($this->never())->method('prepare');
        $artist = $this->manager->get('Artist', array('limit'=>2));
    }

    public function testOrderByMulti()
    {
        $eventArtists = $this->manager->getList('EventArtist', array(
            'limit'=>3, 
            'where'=>'eventId=1',
            'order'=>array('priority', 'sequence'=>'desc')
        ));
        
        $this->assertTrue(is_array($eventArtists));
        
        $result = array();
        foreach ($eventArtists as $ea) {
            $result[] = array($ea->priority, $ea->sequence);
        }
        
        $this->assertEquals(array(
            array(1, 2),
            array(1, 1),
            array(2, 1),
        ), $result);
    }
    
    /*
    public function testWhereClauseBuiltFromArray()
    {
        // TODO: this won't work at the moment as it can't tell the difference between the 'where' array
        // and a criteria array 
        $artists = $this->manager->getList('Artist', array('artistType'=>2));
        $this->assertEquals(2, count($artists));
    }
    */
}
