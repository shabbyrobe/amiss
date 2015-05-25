<?php
namespace Amiss\Test\Cookbook;

/**
 * This test doesn't need to run against MySQL - enough is proven by running
 * against sqlite only.
 */
class MultiSchemaNoteTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->connector = new \PDOK\Connector('sqlite::memory:');
        $this->connector->exec("ATTACH DATABASE ':memory:' AS schema_one;");
        $this->connector->exec("ATTACH DATABASE ':memory:' AS schema_two;");
        $this->connector->exec("CREATE TABLE schema_one.table_one(id INTEGER PRIMARY KEY AUTOINCREMENT, oneName STRING, twoId INTEGER)");
        $this->connector->exec("CREATE TABLE schema_two.table_two(id INTEGER PRIMARY KEY AUTOINCREMENT, twoName STRING)");
        
        $this->manager = \Amiss\Sql\Factory::createManager($this->connector);
        $this->mapper = $this->manager->mapper;
        $this->mapper->objectNamespace = __NAMESPACE__;
    }
    
    public function testInsert()
    {
        $one = new MultiSchemaNoteTestOne();
        $one->oneName = 'foo';
        $this->manager->insert($one);
        
        $data = $this->connector->query('SELECT * FROM schema_one.table_one')->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1, $data);
        $this->assertEquals(array('id'=>'1', 'oneName'=>'foo', 'twoId'=>null), $data[0]);
    }
    
    public function testSelect()
    {
        $this->connector->query('INSERT INTO schema_one.table_one(id, oneName) VALUES(1, "bleargh")');
        
        $obj = $this->manager->getById('MultiSchemaNoteTestOne', 1);
        
        $this->assertEquals('bleargh', $obj->oneName);
        $this->assertEquals(1, $obj->id);
    }
    
    public function testRelatedOne()
    {
        $this->connector->query('INSERT INTO schema_one.table_one(id, oneName, twoId) VALUES(1, "bleargh", 1)');
        $this->connector->query('INSERT INTO schema_two.table_two(id, twoName) VALUES(1, "wahey")');
        
        $obj = $this->manager->getById('MultiSchemaNoteTestOne', 1);
        $this->manager->assignRelated($obj, 'two');
        
        $this->assertTrue($obj->two instanceof MultiSchemaNoteTestTwo);
        $this->assertEquals('wahey', $obj->two->twoName);
    }
    
    public function testRelatedMany()
    {
        $this->connector->query('INSERT INTO schema_one.table_one(id, oneName, twoId) VALUES(1, "bleargh", 1)');
        $this->connector->query('INSERT INTO schema_one.table_one(id, oneName, twoId) VALUES(2, "weehaw", 1)');
        $this->connector->query('INSERT INTO schema_two.table_two(id, twoName) VALUES(1, "wahey")');
        
        $obj = $this->manager->getById('MultiSchemaNoteTestTwo', 1);
        $this->manager->assignRelated($obj, 'ones');
        
        $this->assertTrue(is_array($obj->ones));
        $this->assertTrue(current($obj->ones) instanceof MultiSchemaNoteTestOne);
        $this->assertEquals('bleargh', $obj->ones[0]->oneName);
        $this->assertEquals('weehaw', $obj->ones[1]->oneName);
    }
}

/**
 * :amiss = {"table": "table_one", "schema": "schema_one"};
 */
class MultiSchemaNoteTestOne
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $id;
    
    /** :amiss = {"field": true}; */
    public $oneName;
    
    /** :amiss = {"field": {"index": true}}; */
    public $twoId;
    
    /**
     * :amiss = {"has": {
     *     "type": "one",
     *     "of"  : "MultiSchemaNoteTestTwo",
     *     "from": "twoId"
     * }};
     */
    public $two;
}

/**
 * :amiss = {"table": "table_two", "schema": "schema_two"};
 */
class MultiSchemaNoteTestTwo
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $id;
    
    /** :amiss = {"field": true}; */
    public $twoName;
    
    /**
     * :amiss = {"has": {
     *     "type": "many",
     *     "of"  : "MultiSchemaNoteTestOne",
     *     "to"  : "twoId"
     * }};
     */
    public $ones = array();
}
