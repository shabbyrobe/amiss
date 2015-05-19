<?php
namespace Amiss\Test\Acceptance;

use Amiss\Demo;

class GetByIdTest extends \CustomMapperTestCase
{
    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdKeySingle()
    {
        list ($manager, $ns) = $this->createDefaultNoteManager('
            class Pants {
                /** :amiss = {"field": {"index": {"key": true}}}; */
                public $slug;

                /** :amiss = {"field": true}; */
                public $name;
            }
        ');
        $manager->insertTable('Pants', ['slug'=>'yes', 'name'=>'Yep!']);
        $a = $manager->getById('Pants', 'yes', ['key'=>'slug']);
        $this->assertEquals('Yep!', $a->name);
    }

    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdKeyMultiPositional()
    {
        $manager = $this->createDefaultArrayManager([
            'Pants'=>[
                'class'   => 'stdClass',
                'primary' => ['pri1', 'pri2'],
                'indexes' => ['idx'=>['key'=>true, 'fields'=>['key1', 'key2']]],
                'fields'  => [
                    'pri1'=>true, 'pri2'=>true,
                    'key1'=>true, 'key2'=>true,
                ],
            ],
        ]);
        $manager->insertTable('Pants', ['pri1'=>'p1', 'pri2'=>'p2', 'key1'=>'k1', 'key2'=>'k2']);

        $result = $manager->getById('Pants', ['k1', 'k2'], ['key'=>'idx']);
        $this->assertEquals("k1", $result->key1);
        $this->assertEquals("k2", $result->key2);
    }
    
    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdKeyMultiNamed()
    {
        $manager = $this->createDefaultArrayManager([
            'Pants'=>[
                'class'   => 'stdClass',

                // add a separate primary and unique key so that we can be sure the
                // access is using the righto ne
                'primary' => ['pri1', 'pri2'],
                'indexes' => ['idx'=>['key'=>true, 'fields'=>['key1', 'key2']]],

                'fields'  => [
                    'pri1'=>true, 'pri2'=>true,
                    'key1'=>true, 'key2'=>true,
                ],
            ],
        ]);
        $manager->insertTable('Pants', ['pri1'=>'p1', 'pri2'=>'p2', 'key1'=>'k1', 'key2'=>'k2']);

        $result = $manager->getById('Pants', array('key2'=>'k2', 'key1'=>'k1'), ['key'=>'idx']);
        $this->assertEquals("k1", $result->key1);
        $this->assertEquals("k2", $result->key2);
    }

    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdKeyMultiNamedWithTranslatedNames()
    {
        $manager = $this->createDefaultArrayManager([
            'Pants'=>[
                'class'   => 'stdClass',
                'table'   => 'pa_nts',

                // add a separate primary and unique key so that we can be sure the
                // access is using the righto ne
                'primary' => ['pri1', 'pri2'],
                'indexes' => ['idx'=>['key'=>true, 'fields'=>['key1', 'key2']]],

                'fields'  => [
                    'pri1'=>'pri_1', 'pri2'=>'pri_2',
                    'key1'=>'key_1', 'key2'=>'key_2',
                ],
            ],
        ]);
        $manager->insertTable('Pants', ['pri1'=>'p1', 'pri2'=>'p2', 'key1'=>'k1', 'key2'=>'k2']);

        $result = $manager->getById('Pants', array('key2'=>'k2', 'key1'=>'k1'), ['key'=>'idx']);
        $this->assertEquals("k1", $result->key1);
        $this->assertEquals("k2", $result->key2);
        
        // sanity check to make sure the underlying table actually uses the translated names
        $rows = $manager->getConnector()->query("SELECT pri_1, pri_2, key_1, key_2 FROM pa_nts")->fetchAll(\PDO::FETCH_ASSOC);
        $expectedRows = [['pri_1'=>'p1', 'pri_2'=>'p2', 'key_1'=>'k1', 'key_2'=>'k2']];
        $this->assertEquals($expectedRows, $rows);
    }

    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdPrimarySingle()
    {
        $manager = $this->createDefaultArrayManager([
            'Pants'=>[
                'class'   => 'stdClass',
                'primary' => 'foo',
                'fields'  => ['foo'=>true, 'bar'=>true],
            ],
        ]);
        $manager->insertTable('Pants', ['foo'=>1, 'bar'=>'yep']);
        $result = $manager->getById('Pants', 1);
        $this->assertEquals(1, $result->foo);
        $this->assertEquals('yep', $result->bar);
    }

    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdArgs()
    {
        list ($manager, $ns) = $this->createDefaultNoteManager('
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;
                function __construct($a, $b) {
                    $this->a = $a;
                    $this->b = $b;
                }
            }
        ');
        $manager->insertTable('Pants', ['id'=>100]);
        $result = $manager->getById('Pants', 100, ['args'=>['ding', 'dong']]);
        $this->assertEquals(100, $result->id);
        $this->assertEquals("ding", $result->a);
        $this->assertEquals("dong", $result->b);
    }

    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdPrimaryMultiPositional()
    {
        $manager = $this->createDefaultArrayManager([
            'Pants'=>[
                'class'   => 'stdClass',
                'primary' => ['foo', 'bar'],
                'fields'  => ['foo'=>true, 'bar'=>true],
            ],
        ]);
        $manager->insertTable('Pants', ['foo'=>2, 'bar'=>1]);
        $result = $manager->getById('Pants', array(2, 1));
        $this->assertEquals(2, $result->foo);
        $this->assertEquals(1, $result->bar);
    }
    
    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdMultiNamed()
    {
        $manager = $this->createDefaultArrayManager([
            'Pants'=>[
                'class'   => 'stdClass',
                'primary' => ['foo', 'bar'],
                'fields'  => ['foo'=>true, 'bar'=>true],
            ],
        ]);
        $manager->insertTable('Pants', ['foo'=>2, 'bar'=>1]);
        $result = $manager->getById('Pants', ['bar'=>1, 'foo'=>2]);
        $this->assertEquals(2, $result->foo);
        $this->assertEquals(1, $result->bar);
    }
}
