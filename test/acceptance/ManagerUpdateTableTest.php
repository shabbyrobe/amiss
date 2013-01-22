<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\Criteria\Update;

class ManagerUpdateTableTest extends \SqliteDataTestCase
{
    public function setUp()
    {
        parent::setUp();
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, array $set , array $where )
     * 
     * @group acceptance
     * @group manager
     *
     * // TOO TRICKY - confused signature with criteria array.
     *     
     *     public function testUpdateTableWithArraySetAndArrayWhere()
     *     {
     *         $this->assertEquals(4, $this->manager->count('Artist', 'artistTypeId=?', 1));
     *         
     *         $this->manager->update('Artist', array('artistTypeId'=>1), array('artistTypeId'=>2));
     *         
     *         $this->assertEquals(6, $this->manager->count('Artist', 'artistTypeId=?', 1));
     *     }
     *     
     */

    /**
     * @group acceptance
     * @group manager
     */
    public function testUpdateTableAllowsNonKeyedArraySet()
    {
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(1, $min);
        
        $this->manager->update('EventArtist', array('set'=>array('priority=priority+10'), 'where'=>'1=1'));
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(11, $min);
    }
    
    /**
     * @group acceptance
     * @group manager
     */
    public function testUpdateTableAllowsNonKeyedItemMixedInWithParameterForSet()
    {
        $count = $this->manager->count('EventArtist');
        $this->assertGreaterThan(0, $count);
        
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(1, $min);
        
        $this->manager->update('EventArtist', array('set'=>array('priority=priority+10', 'sequence'=>15001), 'where'=>'1=1'));
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(11, $min);
        
        $stmt = $this->manager->getConnector()->prepare("SELECT COUNT(*) FROM event_artist WHERE sequence=15001");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals($count, $min);
    }

    /**
     * @group acceptance
     * @group manager
     * @expectedException InvalidArgumentException
     */
    public function testUpdateTableFailsWithNoWhereClause()
    {
        $this->manager->update('EventArtist', array('set'=>array('priority=priority+10')));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, array $set , string $positionalWhere, [ $param1, ... ] )
     * 
      * @group acceptance
     * @group manager
     */
    public function testUpdateTableWithArraySetAndPositionalWhere()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $this->manager->update('Artist', array('artistTypeId'=>1), 'artistTypeId=?', 2);
        
        $this->assertEquals(12, $this->manager->count('Artist', 'artistTypeId=?', 1));
    }
    
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, array $set , string $namedWhere, array $params )
     * 
      * @group acceptance
     * @group manager
     */
    public function testUpdateTableWithArraySetAndNamedWhere()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $this->manager->update('Artist', array('artistTypeId'=>1), 'artistTypeId=:id', array(':id'=>2));
        
        $this->assertEquals(12, $this->manager->count('Artist', 'artistTypeId=?', 1));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, array $criteria )
     * 
      * @group acceptance
     * @group manager
     */
    public function testUpdateTableWithArrayCriteria()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $this->manager->update('Artist', array('set'=>array('artistTypeId'=>1), 'where'=>'artistTypeId=:id', 'params'=>array(':id'=>2)));
        
        $this->assertEquals(12, $this->manager->count('Artist', 'artistTypeId=?', 1));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, Criteria\Update $criteria )
     * 
      * @group acceptance
     * @group manager
     */
    public function testUpdateTableWithObjectCriteria()
    {
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', 1));
        
        $criteria = new Update(array('set'=>array('artistTypeId'=>1), 'where'=>'artistTypeId=:id', 'params'=>array(':id'=>2)));
        $this->manager->update('Artist', $criteria);
        
        $this->assertEquals(12, $this->manager->count('Artist', 'artistTypeId=?', 1));
    }
}
