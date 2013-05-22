<?php
namespace Amiss\Test\Acceptance;

class ManagerSelectTest extends \ModelDataTestCase
{
    /**
     * @group acceptance
     * @group manager 
     */
    public function testGetByIdSingle()
    {
        $a = $this->manager->getById('Artist', 1);
        $this->assertTrue($a instanceof \Amiss\Demo\Artist);
        $this->assertEquals('Limozeen', $a->name);
    }

    /**
     * @group acceptance
     * @group manager 
     */
    public function testGetByIdMultiPositional()
    {
        $a = $this->manager->getById('EventArtist', array(2, 1));
        $this->assertTrue($a instanceof \Amiss\Demo\EventArtist);
        $this->assertEquals(2, $a->eventId);
        $this->assertEquals(1, $a->artistId);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testGetByIdMultiNamed()
    {
        $a = $this->manager->getById('EventArtist', array('eventId'=>2, 'artistId'=>2));
        $this->assertTrue($a instanceof \Amiss\Demo\EventArtist);
        $this->assertEquals(2, $a->eventId);
        $this->assertEquals(2, $a->artistId);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testSingleObjectPositionalParametersShorthand()
    {
        $a = $this->manager->get('Artist', 'slug=?', 'limozeen');
        $this->assertTrue($a instanceof \Amiss\Demo\Artist);
        $this->assertEquals('Limozeen', $a->name);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testSingleObjectNamedParametersShorthand()
    {
        $a = $this->manager->get('Artist', 'slug=:slug', array(':slug'=>'limozeen'));
        $this->assertTrue($a instanceof \Amiss\Demo\Artist);
        $this->assertEquals('Limozeen', $a->name);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
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
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testSingleObjectUsingCriteria()
    {
        $criteria = new \Amiss\Sql\Criteria\Select;
        $criteria->where = 'slug=:slug';
        $criteria->params[':slug'] = 'limozeen';
        
        $a = $this->manager->get('Artist', $criteria);
        
        $this->assertTrue($a instanceof \Amiss\Demo\Artist);
        $this->assertEquals('Limozeen', $a->name);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testList()
    {
        $artists = $this->manager->getList('Artist');
        $this->assertTrue(is_array($artists));
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('limozeen', current($artists)->slug);
        next($artists);
        $this->assertEquals('taranchula', current($artists)->slug);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testPagedListFirstPage()
    {
        $artists = $this->manager->getList('Artist', array('page'=>array(1, 3)));
        $this->assertEquals(3, count($artists));
        
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('limozeen', current($artists)->slug);
        next($artists);
        $this->assertEquals('taranchula', current($artists)->slug);
    }

    /**
     * @group acceptance
     * @group manager 
     */
    public function testPagedListSecondPage()
    {
        $artists = $this->manager->getList('Artist', array('page'=>array(2, 3)));
        $this->assertEquals(3, count($artists));
        
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('george-carlin', current($artists)->slug);
        next($artists);
        $this->assertEquals('david-cross', current($artists)->slug);
    }

    /**
     * @group acceptance
     * @group manager 
     */
    public function testListLimit()
    {
        $artists = $this->manager->getList('Artist', array('limit'=>3));
        $this->assertEquals(3, count($artists));
        
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('limozeen', current($artists)->slug);
        next($artists);
        $this->assertEquals('taranchula', current($artists)->slug);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testListOffset()
    {
        $artists = $this->manager->getList('Artist', array('limit'=>3, 'offset'=>3));
        $this->assertEquals(3, count($artists));
        
        $this->assertTrue(current($artists) instanceof \Amiss\Demo\Artist);
        $this->assertEquals('george-carlin', current($artists)->slug);
        next($artists);
        $this->assertEquals('david-cross', current($artists)->slug);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testOrderByManualImpliedAsc()
    {
        $artists = $this->manager->getList('Artist', array('order'=>'name'));
        $this->assertTrue(is_array($artists));
        $this->assertEquals('anvil', current($artists)->slug);
        foreach ($artists as $a); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals('the-sonic-manipulator', $a->slug);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testOrderByManualDesc()
    {
        $artists = $this->manager->getList('Artist', array('order'=>'name desc'));
        $this->assertTrue(is_array($artists));
        $this->assertEquals('the-sonic-manipulator', current($artists)->slug);
        foreach ($artists as $a); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals('anvil', $a->slug);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
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
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testOrderBySingleLongForm()
    {
        $artists = $this->manager->getList('Artist', array('order'=>array('name')));
        $this->assertEquals('anvil', current($artists)->slug);
        $this->assertTrue(is_array($artists));
        foreach ($artists as $a); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals('the-sonic-manipulator', $a->slug);
    }

    /**
     * @group acceptance
     * @group manager 
     */
    public function testOrderBySingleLongFormDescending()
    {
        $artists = $this->manager->getList('Artist', array('order'=>array('name'=>'desc')));
        $this->assertTrue(is_array($artists));
        
        $this->assertEquals('the-sonic-manipulator', current($artists)->slug);
        foreach ($artists as $a); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals('anvil', $a->slug);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testOrderByGetterProperty()
    {
        $events = $this->manager->getList('Event', array('order'=>array('subName')));
        $this->assertTrue(is_array($events));
        
        $this->assertEquals(2, current($events)->eventId);
        foreach ($events as $e); // get the last element regardless of if the array is keyed or indexed
        $this->assertEquals(1, $e->eventId);
    }
    
    /**
     * @group acceptance
     * @group manager
     */
    public function testSelectSingleObjectFromMultipleResultWhenLimitIsOne()
    {
        $artist = $this->manager->get('Artist', array('order'=>array('name'=>'desc'), 'limit'=>1));
        $this->assertTrue($artist instanceof \Amiss\Demo\Artist);
        
        $this->assertEquals('the-sonic-manipulator', $artist->slug);
    }
    
    /**
     * @group acceptance
     * @group manager
     * @expectedException Amiss\Exception
     */
    public function testSelectSingleObjectFailsWhenResultReturnsMany()
    {
        $artist = $this->manager->get('Artist', array('order'=>array('name'=>'desc')));
    }
    
    /**
     * @group acceptance
     * @group manager
     * @expectedException Amiss\Exception
     */
    public function testSelectSingleObjectFailsWithoutIssuingQueryWhenLimitSetButNotOne()
    {
        $this->manager->connector = $this->getMock('Amiss\Sql\Connector', array('prepare'), array(''));
        $this->manager->connector->expects($this->never())->method('prepare');
        $artist = $this->manager->get('Artist', array('limit'=>2));
    }
    
    /**
     * @group acceptance
     * @group manager
     */
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
