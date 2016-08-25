<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;
use Amiss\Demo;

/**
 * @group acceptance
 * @group manager 
 */
class ManagerCountTest extends \Amiss\Test\Helper\TestCase
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

    function testCountObjectsAll()
    {
        $count = $this->manager->count(Demo\Artist::class);
        $this->assertEquals(13, $count);
    }
    
    function testCountObjectsWithMultiColPk()
    {
        $count = $this->manager->count(Demo\EventArtist::class);
        $this->assertEquals(9, $count);
    }
    
    public function testCountObjectsPositionalParametersShorthand()
    {
        $count = $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]);
        $this->assertEquals(9, $count);
    }
    
    public function testCountObjectsNamedParametersShorthand()
    {
        $count = $this->manager->count(Demo\Artist::class, 'artistTypeId=:artistTypeId', array(':artistTypeId'=>2));
        $this->assertEquals(3, $count);
    }
    
    public function testCountObjectsNamedParametersLongForm()
    {
        $count = $this->manager->count(
            Demo\Artist::class, 
            array(
                'where'=>'artistTypeId=:artistTypeId', 
                'params'=>array(':artistTypeId'=>1)
            )
        );
        $this->assertEquals(9, $count);
    }
    
    public function testCountObjectsUsingQuery()
    {
        $criteria = new \Amiss\Sql\Query\Select;
        $criteria->where = 'artistTypeId=:artistTypeId';
        $criteria->params[':artistTypeId'] = 1;
        
        $count = $this->manager->count(Demo\Artist::class, $criteria);
        
        $this->assertEquals(9, $count);
    }
}
