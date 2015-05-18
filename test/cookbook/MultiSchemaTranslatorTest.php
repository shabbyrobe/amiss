<?php
namespace Amiss\Test\Cookbook;

/**
 * This test doesn't need to run against MySQL - enough is proven by running
 * against sqlite only.
 */
class MultiSchemaTranslatorTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->connector = new \PDOK\Connector('sqlite::memory:');
        $this->connector->exec("ATTACH DATABASE ':memory:' AS schema_one;");
        $this->connector->exec("ATTACH DATABASE ':memory:' AS schema_two;");
        $this->connector->exec("CREATE TABLE schema_one.multi_schema_translator_test_one(id INTEGER PRIMARY KEY AUTOINCREMENT, oneName STRING, twoId INTEGER)");
        $this->connector->exec("CREATE TABLE schema_two.multi_schema_translator_test_two(id INTEGER PRIMARY KEY AUTOINCREMENT, twoName STRING)");
        
        $this->manager = \Amiss\Sql\Factory::createManager($this->connector);
        $mapper = $this->mapper = $this->manager->mapper;
        $this->mapper->defaultTableNameTranslator = function($name) use ($mapper) {
            if (preg_match('/One$/', $name)) {
                $prefix = 'schema_one.';
            }
            elseif (preg_match('/Two$/', $name)) {
                $prefix = 'schema_two.';
            }
            $table = $mapper->convertUnknownTableName($name);
            return $prefix.$table;
        };
        $this->mapper->objectNamespace = __NAMESPACE__;
    }
    
    public function testInsert()
    {
        $one = new MultiSchemaTranslatorTestOne();
        $one->oneName = 'foo';
        $this->manager->insert($one);
        
        $data = $this->connector->query('SELECT * FROM schema_one.multi_schema_translator_test_one')->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1, $data);
        $this->assertEquals(array('id'=>'1', 'oneName'=>'foo', 'twoId'=>null), $data[0]);
    }
    
    public function testSelect()
    {
        $this->connector->query('INSERT INTO schema_one.multi_schema_translator_test_one(id, oneName) VALUES(1, "bleargh")');
        
        $obj = $this->manager->getById('MultiSchemaTranslatorTestOne', 1);
        
        $this->assertEquals('bleargh', $obj->oneName);
        $this->assertEquals(1, $obj->id);
    }
    
    public function testRelatedOne()
    {
        $this->connector->query('INSERT INTO schema_one.multi_schema_translator_test_one(id, oneName, twoId) VALUES(1, "bleargh", 1)');
        $this->connector->query('INSERT INTO schema_two.multi_schema_translator_test_two(id, twoName) VALUES(1, "wahey")');
        
        $obj = $this->manager->getById('MultiSchemaTranslatorTestOne', 1);
        $this->manager->assignRelated($obj, 'two');
        
        $this->assertTrue($obj->two instanceof MultiSchemaTranslatorTestTwo);
        $this->assertEquals('wahey', $obj->two->twoName);
    }
    
    public function testRelatedMany()
    {
        $this->connector->query('INSERT INTO schema_one.multi_schema_translator_test_one(id, oneName, twoId) VALUES(1, "bleargh", 1)');
        $this->connector->query('INSERT INTO schema_one.multi_schema_translator_test_one(id, oneName, twoId) VALUES(2, "weehaw", 1)');
        $this->connector->query('INSERT INTO schema_two.multi_schema_translator_test_two(id, twoName) VALUES(1, "wahey")');
        
        $obj = $this->manager->getById('MultiSchemaTranslatorTestTwo', 1);
        $this->manager->assignRelated($obj, 'ones');
        
        $this->assertTrue(is_array($obj->ones));
        $this->assertTrue(current($obj->ones) instanceof MultiSchemaTranslatorTestOne);
        $this->assertEquals('bleargh', $obj->ones[0]->oneName);
        $this->assertEquals('weehaw', $obj->ones[1]->oneName);
    }
}

class MultiSchemaTranslatorTestOne
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
     *     "of"  : "MultiSchemaTranslatorTestTwo",
     *     "from": "twoId"
     * }};
     */
    public $two;
}

class MultiSchemaTranslatorTestTwo
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $id;
    
    /** :amiss = {"field": true}; */
    public $twoName;
    
    /**
     * :amiss = {"has": {
     *     "type": "many",
     *     "of": "MultiSchemaTranslatorTestOne",
     *     "to": "twoId"
     * }};
     */
    public $ones = array();
}
