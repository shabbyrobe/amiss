<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\Query\Criteria;

class ManagerDeleteFromTableTest extends \Amiss\Test\Helper\ModelDataTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithMatchAllClause()
    {
        $this->assertGreaterThan(0, $this->manager->count('Artist'));
        $this->manager->deleteTable('Artist', '1=1');
        $this->assertEquals(0, $this->manager->count('Artist'));
    }
    
    /**
     * @group acceptance
     * @group manager
     * @expectedException InvalidArgumentException
     */
    public function testDeleteTableWithoutClauseFails()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $this->manager->deleteTable('Artist');
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->deleteTable( string $table, string $positionalWhere, [ $param1, ... ] )
     * 
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithArraySetAndPositionalWhere()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $this->manager->deleteTable('Artist', 'artistTypeId=?', [1]);
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->deleteTable( string $table, string $namedWhere, array $params )
     * 
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithArraySetAndNamedWhere()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $this->manager->deleteTable('Artist', 'artistTypeId=:id', array(':id'=>1));
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->deleteTable( string $table, array $criteria )
     * 
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithArrayCriteria()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $this->manager->deleteTable('Artist', array('where'=>'artistTypeId=:id', 'params'=>array(':id'=>1)));
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', [1]));

        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->deleteTable( string $table, Criteria\Query $criteria )
     * 
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithObjectCriteria()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $criteria = new Criteria(array('where'=>'artistTypeId=:id', 'params'=>array(':id'=>1)));
        $this->manager->deleteTable('Artist', $criteria);
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
}
