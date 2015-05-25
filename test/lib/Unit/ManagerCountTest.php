<?php
namespace Amiss\Test\Unit;

/**
 * @group unit
 */
class ManagerCountTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->db = new \Amiss\Test\Helper\TestConnector('sqlite::memory:');
        $this->mapper = new \Amiss\Test\Helper\TestMapper();
        $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
    }
    
    /**
     * @group manager
     * @covers Amiss\Sql\Manager::count
     */
    public function testCountQueryWithoutPrimary()
    {
        $this->mapper->meta['stdClass'] = new \Amiss\Meta('stdClass', array('table'=>'std_class'));
        $this->manager->count('stdClass');
        
        $expected = 'SELECT COUNT(1) FROM `std_class`';
        $found = $this->db->getLastCall();
        
        $this->assertLoose($expected, $found[0]);
    }
    
    /**
     * @group manager
     * @covers Amiss\Sql\Manager::count
     */
    public function testCountQueryWithSingleColumnPrimary()
    {
        $this->mapper->meta['stdClass'] =  new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'primary'=>array(
                'a'
            ),
            'fields'=>array(
                'a'=>array('name'=>'a_field'),
                'b'=>array('name'=>'b_field'),
            ),
        ));
        
        $this->manager->count('stdClass');
        
        $expected = 'SELECT COUNT(1) FROM `std_class`';
        $found = $this->db->getLastCall();
        
        $this->assertLoose($expected, $found[0]);
    }
    
    /**
     * @group manager
     * @covers Amiss\Sql\Manager::count
     */
    public function testCountQueryWithMultiColumnPrimary()
    {
        $this->mapper->meta['stdClass'] =  new \Amiss\Meta('stdClass', array(
            'table'=>'std_class',
            'primary'=>array(
                'a', 'b'
            ),
            'fields'=>array(
                'a'=>array('name'=>'a_field'),
                'b'=>array('name'=>'b_field'),
            ),
        ));
        
        $this->manager->count('stdClass');
        
        $expected = 'SELECT COUNT(1) FROM `std_class`';
        $found = $this->db->getLastCall();
        
        $this->assertLoose($expected, $found[0]);
    }
}
