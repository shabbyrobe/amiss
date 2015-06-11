<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\Query\Criteria;
use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class ManagerDeleteFromTableTest extends \Amiss\Test\Helper\TestCase
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

    public function testDeleteTableWithMatchAllClause()
    {
        $this->assertGreaterThan(0, $this->deps->manager->count('Artist'));
        $this->manager->deleteTable('Artist', '1=1');
        $this->assertEquals(0, $this->deps->manager->count('Artist'));
    }
    
    public function testDeleteTableWithoutClauseFails()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        $this->setExpectedException(\InvalidArgumentException::class);
        $this->deps->manager->deleteTable('Artist');
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->deleteTable( string $table, string $positionalWhere, [ $param1, ... ] )
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
