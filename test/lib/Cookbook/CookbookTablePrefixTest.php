<?php
namespace Amiss\Test\Cookbook;

class CookbookTablePrefixTest extends \Amiss\Test\Helper\TestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $mapper = $this->mapper = new \Amiss\Mapper\Note();
        
        $translator = new \Amiss\Name\CamelToUnderscore();
        
        $this->mapper->defaultTableNameTranslator = function($objectName) use ($mapper, $translator) {
            return 'yep_'.$mapper->convertUnknownTableName($objectName);
        };

        $this->manager = new \Amiss\Sql\Manager(array(), $this->mapper);
    }
    
    /**
     * @group cookbook
     */
    public function testRetrieve()
    {
        $meta = $this->manager->getMeta(CookbookTablePrefixObject::class);
        $this->assertEquals('yep_cookbook_table_prefix_object', $meta->table);
    }
}

/** :amiss = true; */
class CookbookTablePrefixObject
{
    /**
     * :amiss = {"field": {"primary": true, "type": "autoinc"}};
     */
    public $id;
    
    /**
     * :amiss = {"field": {"type": "pants", "name": "thing_part1"}};
     */
    public $thing;
}
