<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;
use Amiss\Sql\Query\Criteria;
use Amiss\Sql\Query\Update;
use Amiss\Test;

/**
 * @group acceptance
 * @group manager
 */
class ManagerUpdateTableTest extends \Amiss\Test\Helper\TestCase
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

    public function testUpdateTableAllowsStringSet()
    {
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(1, $min);
        
        $this->manager->updateTable(Demo\EventArtist::class, array('set'=>'priority=priority+10', 'where'=>'1=1'));
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(11, $min);
    }
    
    public function testUpdateTableAllowsStringSetWithArrayWhere()
    {
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(1, $min);
        
        $this->manager->updateTable(Demo\EventArtist::class, 'priority=priority+10', ['where'=>'priority>=3']);
        $stmt = $this->manager->getConnector()->prepare("SELECT MAX(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(2010, $min);
    }
    
    public function testUpdateTableAllowsStringSetWithStringWhereParameters()
    {
        $count = $this->manager->count(Demo\EventArtist::class);
        $this->assertGreaterThan(0, $count);
        
        $stmt = $this->manager->getConnector()->prepare("SELECT MIN(priority) FROM event_artist");
        $stmt->execute();
        $min = $stmt->fetchColumn();
        $this->assertEquals(1, $min);
        
        $this->manager->updateTable(Demo\EventArtist::class, 'priority=priority+?', 'priority>=?', [10, 3]);
        $stmt = $this->manager->getConnector()->prepare("SELECT priority, COUNT(priority) as cnt FROM event_artist GROUP BY priority");
        $stmt->execute();
        $priorities = $stmt->fetchAll(\PDO::FETCH_NUM);
        $this->assertEquals([[1, 5], [2, 1], [13, 2], [2010, 1]], $priorities);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testUpdateTableFailsWithNoWhereClause()
    {
        $this->manager->updateTable(Demo\EventArtist::class, array('set'=>'priority=priority+10'));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, array $set , string $positionalWhere, [ $param1, ... ] )
     */
    public function testUpdateTableWithArraySetAndPositionalWhere()
    {
        $this->assertEquals(9, $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]));
        
        $this->manager->updateTable(Demo\Artist::class, array('artistTypeId'=>1), 'artistTypeId=?', [2]);
        
        $this->assertEquals(12, $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, array $set , string $namedWhere, array $params )
     */
    public function testUpdateTableWithArraySetAndNamedWhere()
    {
        $this->assertEquals(9, $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]));
        
        $this->manager->updateTable(Demo\Artist::class, array('artistTypeId'=>1), 'artistTypeId=:id', array(':id'=>2));
        
        $this->assertEquals(12, $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, array $criteria )
     */
    public function testUpdateTableWithArrayCriteria()
    {
        $this->assertEquals(9, $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]));
        
        $this->manager->updateTable(Demo\Artist::class, array('set'=>array('artistTypeId'=>1), 'where'=>'artistTypeId=:id', 'params'=>array(':id'=>2)));
        
        $this->assertEquals(12, $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]));
    }
    
    /**
     * Ensures the following signature works as expected:
     *   Amiss\Sql\Manager->update( string $table, Criteria\Update $criteria )
     */
    public function testUpdateTableWithObjectCriteria()
    {
        $this->assertEquals(9, $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]));
        
        $criteria = new Update(array('set'=>array('artistTypeId'=>1), 'where'=>'artistTypeId=:id', 'params'=>array(':id'=>2)));
        $this->manager->updateTable(Demo\Artist::class, $criteria);
        
        $this->assertEquals(12, $this->manager->count(Demo\Artist::class, 'artistTypeId=?', [1]));
    }

    public function testUpdateTableValuesUseTypeHandlers()
    {
        $this->manager->updateTable(
            Demo\Event::class, 
            [
                'dateStart'=>new \DateTime('2030-01-01 11:11+00:00'),
                'dateEnd'=>new \DateTime('2030-02-02 11:11+00:00'),
            ],
            ['where'=>['eventId'=>1]]
        );
        $event = $this->manager->getById(Demo\Event::class, 1);
        $this->assertEquals(new \DateTime('2030-01-01 11:11+00:00'), $event->dateStart);
        $this->assertEquals(new \DateTime('2030-02-02 11:11+00:00'), $event->dateEnd);
    }
}
