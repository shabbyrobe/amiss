<?php
namespace Amiss\Test\Acceptance;

use Amiss\Sql\Query\Criteria;
use Amiss\Sql\Query\Update;

class ManagerUpdateTableTest extends \ModelDataTestCase
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
    public function testUpdateTableAllowsStringSet()
    {
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(1, $min);
        
        $this->manager->updateTable('EventArtist', array('set'=>'priority=priority+10', 'where'=>'1=1'));
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(11, $min);
    }
    
    /**
     * @group acceptance
     * @group manager
     */
    public function testUpdateTableAllowsStringSetWithArrayWhere()
    {
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(1, $min);
        
        $this->manager->updateTable('EventArtist', 'priority=priority+10', ['where'=>'priority>=3']);
        $stmt = $this->manager->getConnector()->prepare("SELECT MAX(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(2010, $min);
    }
    
    /**
     * @group acceptance
     * @group manager
     */
    public function testUpdateTableAllowsStringSetWithStringWhereParameters()
    {
        $count = $this->manager->count('EventArtist');
        $this->assertGreaterThan(0, $count);
        
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(1, $min);
        
        $this->manager->updateTable('EventArtist', 'priority=priority+?', 'priority>=?', [10, 3]);
        $stmt = $this->manager->getConnector()->prepare("SELECT priority, COUNT(priority) as cnt FROM event_artist GROUP BY priority");
        $stmt->execute();
        $priorities = $stmt->fetchAll(\PDO::FETCH_NUM);
        $this->assertEquals([[1, 5], [2, 1], [13, 2], [2010, 1]], $priorities);
    }

    /**
     * @group acceptance
     * @group manager
     * @expectedException InvalidArgumentException
     */
    public function testUpdateTableFailsWithNoWhereClause()
    {
        $this->manager->updateTable('EventArtist', array('set'=>'priority=priority+10'));
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
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $this->manager->updateTable('Artist', array('artistTypeId'=>1), 'artistTypeId=?', [2]);
        
        $this->assertEquals(12, $this->manager->count('Artist', 'artistTypeId=?', [1]));
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
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $this->manager->updateTable('Artist', array('artistTypeId'=>1), 'artistTypeId=:id', array(':id'=>2));
        
        $this->assertEquals(12, $this->manager->count('Artist', 'artistTypeId=?', [1]));
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
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $this->manager->updateTable('Artist', array('set'=>array('artistTypeId'=>1), 'where'=>'artistTypeId=:id', 'params'=>array(':id'=>2)));
        
        $this->assertEquals(12, $this->manager->count('Artist', 'artistTypeId=?', [1]));
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
        $this->assertEquals(9, $this->manager->count('Artist', 'artistTypeId=?', [1]));
        
        $criteria = new Update(array('set'=>array('artistTypeId'=>1), 'where'=>'artistTypeId=:id', 'params'=>array(':id'=>2)));
        $this->manager->updateTable('Artist', $criteria);
        
        $this->assertEquals(12, $this->manager->count('Artist', 'artistTypeId=?', [1]));
    }

    public function testUpdateTableValuesUseTypeHandlers()
    {
        $this->manager->updateTable(
            'Event', 
            [
                'dateStart'=>new \DateTime('2030-01-01 11:11'),
                'dateEnd'=>new \DateTime('2030-02-02 11:11'),
            ],
            ['where'=>['eventId'=>1]]
        );
        $event = $this->manager->getById('Event', 1);
        $this->assertEquals(new \DateTime('2030-01-01 11:11'), $event->dateStart);
        $this->assertEquals(new \DateTime('2030-02-02 11:11'), $event->dateEnd);
    }
}
