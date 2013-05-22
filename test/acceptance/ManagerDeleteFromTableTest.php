<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\Criteria\Query;

class ManagerDeleteFromTableTest extends \ModelDataTestCase
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
        $this->manager->delete('Artist', '1=1');
        $this->assertEquals(0, $this->manager->count('Artist'));
    }
    
    /**
     * @group acceptance
     * @group manager
     * @expectedException InvalidArgumentException
     */
    public function testDeleteTableWithoutClauseFails()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $this->manager->delete('Artist');
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->delete( string $table, string $positionalWhere, [ $param1, ... ] )
     * 
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithArraySetAndPositionalWhere()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $this->manager->delete('Artist', 'artistTypeId=?', 1);
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->delete( string $table, string $namedWhere, array $params )
     * 
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithArraySetAndNamedWhere()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $this->manager->delete('Artist', 'artistTypeId=:id', array(':id'=>1));
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->delete( string $table, array $criteria )
     * 
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithArrayCriteria()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $this->manager->delete('Artist', array('where'=>'artistTypeId=:id', 'params'=>array(':id'=>1)));
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));

        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->delete( string $table, Criteria\Query $criteria )
     * 
     * @group acceptance
     * @group manager
     */
    public function testDeleteTableWithObjectCriteria()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $criteria = new Query(array('where'=>'artistTypeId=:id', 'params'=>array(':id'=>1)));
        $this->manager->delete('Artist', $criteria);
        
        $this->assertEquals(0, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        // sanity check: make sure we didn't delete everything!
        $this->assertEquals(4, $this->manager->count('Artist'));
    }
}
