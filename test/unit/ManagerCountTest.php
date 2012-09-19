<?php

namespace Amiss\Test\Unit;

class ManagerCountTest extends \CustomTestCase
{
    public function setUp()
    {
        $this->db = new \TestConnector('sqlite::memory:');
        $this->mapper = new \TestMapper();
        $this->manager = new \Amiss\Manager($this->db, $this->mapper);
    }
    
    /**
     * @group unit
     * @group manager
     * @covers Amiss\Manager::count
     */
    public function testCountQueryWithoutPrimary()
    {
        $this->mapper->meta['stdClass'] = new \Amiss\Meta('stdClass', 'std_class', array());
        $this->manager->count('stdClass');
        
        $expected = 'SELECT COUNT(*) FROM std_class';
        $found = $this->db->getLastCall();
        
        $this->assertLoose($expected, $found[0]);
    }
    
    /**
     * @group unit
     * @group manager
     * @covers Amiss\Manager::count
     */
    public function testCountQueryWithSingleColumnPrimary()
    {
        $this->mapper->meta['stdClass'] =  new \Amiss\Meta('stdClass', 'std_class', array(
            'primary'=>array(
                'a'
            ),
            'fields'=>array(
                'a'=>array('name'=>'a_field'),
                'b'=>array('name'=>'b_field'),
            ),
        ));
        
        $this->manager->count('stdClass');
        
        $expected = 'SELECT COUNT(a_field) FROM std_class';
        $found = $this->db->getLastCall();
        
        $this->assertLoose($expected, $found[0]);
    }
    
    /**
     * @group unit
     * @group manager
     * @covers Amiss\Manager::count
     */
    public function testCountQueryWithMultiColumnPrimary()
    {
        $this->mapper->meta['stdClass'] =  new \Amiss\Meta('stdClass', 'std_class', array(
            'primary'=>array(
                'a', 'b'
            ),
            'fields'=>array(
                'a'=>array('name'=>'a_field'),
                'b'=>array('name'=>'b_field'),
            ),
        ));
        
        $this->manager->count('stdClass');
        
        $expected = 'SELECT COUNT(*) FROM std_class';
        $found = $this->db->getLastCall();
        
        $this->assertLoose($expected, $found[0]);
    }
}
