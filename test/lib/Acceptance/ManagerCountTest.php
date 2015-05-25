<?php
namespace Amiss\Test\Acceptance;

class ManagerCountTest extends \Amiss\Test\Helper\ModelDataTestCase
{
    /**
     * @group acceptance
     * @group manager 
     */
    function testCountObjectsAll()
    {
        $count = $this->manager->count('Artist');
        $this->assertEquals(13, $count);
    }
    
    /**
     * @group acceptance
     * @group manager
     */
    function testCountObjectsWithMultiColPk()
    {
        $count = $this->manager->count('EventArtist');
        $this->assertEquals(9, $count);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testCountObjectsPositionalParametersShorthand()
    {
        $count = $this->manager->count('Artist', 'artistTypeId=?', [1]);
        $this->assertEquals(9, $count);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testCountObjectsNamedParametersShorthand()
    {
        $count = $this->manager->count('Artist', 'artistTypeId=:artistTypeId', array(':artistTypeId'=>2));
        $this->assertEquals(3, $count);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testCountObjectsNamedParametersLongForm()
    {
        $count = $this->manager->count(
            'Artist', 
            array(
                'where'=>'artistTypeId=:artistTypeId', 
                'params'=>array(':artistTypeId'=>1)
            )
        );
        $this->assertEquals(9, $count);
    }
    
    /**
     * @group acceptance
     * @group manager 
     */
    public function testCountObjectsUsingQuery()
    {
        $criteria = new \Amiss\Sql\Query\Select;
        $criteria->where = 'artistTypeId=:artistTypeId';
        $criteria->params[':artistTypeId'] = 1;
        
        $count = $this->manager->count('Artist', $criteria);
        
        $this->assertEquals(9, $count);
    }
}
