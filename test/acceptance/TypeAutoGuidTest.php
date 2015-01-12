<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;

class TypeAutoGuidTest extends \CustomTestCase
{
    public function setUp()
    {
        parent::setUp();
        
        $this->mapper = new \Amiss\Mapper\Arrays(array(
            'Amiss\Test\Acceptance\TypeAutoGuidFoo'=>array(
                'primary'=>'guid',
                'fields'=>array(
                    'guid'=>array('type'=>'autoguid'),
                    'name',
                ),
            ),
        ));
        $this->mapper->typeHandlers['autoguid'] = new \Amiss\Type\AutoGuid();
        $this->db = new \PDOK\Connector('sqlite::memory:');
        $this->db->exec("CREATE TABLE type_auto_guid_foo(guid STRING PRIMARY KEY, name STRING);");
        $this->manager = new \Amiss\Sql\Manager($this->db, $this->mapper);
        $this->db->queries = 0;
    }
    
    public function testInsertWithEmptyGuidGenerates()
    {
        $f = new TypeAutoGuidFoo();
        $f->name = 'yep';
        $this->manager->insert($f);
        $this->assertRegExp('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/', $f->guid);
    }
}

class TypeAutoGuidFoo
{
    public $guid;
    public $name;
    
    public $getGuidCalled;
    public $setGuidCalled;
    
    public function getGuid()
    {
        $this->getGuidCalled = true;
        return $this->guid;
    }
    
    public function setGuid($value)
    {
        $this->setGuidCalled = true;
        $this->guid = $value;
    }
}
