<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;

class GetByIdTest extends \Amiss\Test\Helper\TestCase
{
    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdKeySingle()
    {
        $deps = Test\Factory::managerNoteModelCustom('
            class Pants {
                /** :amiss = {"field": {"index": {"key": true}}}; */
                public $slug;

                /** :amiss = {"field": true}; */
                public $name;
            }
        ');
        $deps->manager->insertTable('Pants', ['slug'=>'yes', 'name'=>'Yep!']);
        $a = $deps->manager->getById('Pants', 'yes', ['key'=>'slug']);
        $this->assertEquals('Yep!', $a->name);
    }

    /**
     * @group acceptance
     * @group manager 
     * @group getById
     */
    public function testGetByIdKeyMultiPositional()
    {
        $d = Test\Factory::managerArraysModelCustom([
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
        $d->manager->insertTable('Pants', ['pri1'=>'p1', 'pri2'=>'p2', 'key1'=>'k1', 'key2'=>'k2']);

        $result = $d->manager->getById('Pants', ['k1', 'k2'], ['key'=>'idx']);
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
        $d = Test\Factory::managerArraysModelCustom([
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
        $d->manager->insertTable('Pants', ['pri1'=>'p1', 'pri2'=>'p2', 'key1'=>'k1', 'key2'=>'k2']);

        $result = $d->manager->getById('Pants', array('key2'=>'k2', 'key1'=>'k1'), ['key'=>'idx']);
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
        $d = Test\Factory::managerArraysModelCustom([
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
        $d->manager->insertTable('Pants', ['pri1'=>'p1', 'pri2'=>'p2', 'key1'=>'k1', 'key2'=>'k2']);

        $result = $d->manager->getById('Pants', array('key2'=>'k2', 'key1'=>'k1'), ['key'=>'idx']);
        $this->assertEquals("k1", $result->key1);
        $this->assertEquals("k2", $result->key2);
        
        // sanity check to make sure the underlying table actually uses the translated names
        $rows = $d->manager->getConnector()->query("SELECT pri_1, pri_2, key_1, key_2 FROM pa_nts")->fetchAll(\PDO::FETCH_ASSOC);
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
        $d = Test\Factory::managerArraysModelCustom([
            'Pants'=>[
                'class'   => 'stdClass',
                'primary' => 'foo',
                'fields'  => ['foo'=>true, 'bar'=>true],
            ],
        ]);
        $d->manager->insertTable('Pants', ['foo'=>1, 'bar'=>'yep']);
        $result = $d->manager->getById('Pants', 1);
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
        $d = Test\Factory::managerNoteModelCustom('
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;
                function __construct($a, $b) {
                    $this->a = $a;
                    $this->b = $b;
                }
            }
        ');
        $d->manager->insertTable('Pants', ['id'=>100]);
        $result = $d->manager->getById('Pants', 100, ['args'=>['ding', 'dong']]);
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
        $d = Test\Factory::managerArraysModelCustom([
            'Pants'=>[
                'class'   => 'stdClass',
                'primary' => ['foo', 'bar'],
                'fields'  => ['foo'=>true, 'bar'=>true],
            ],
        ]);
        $d->manager->insertTable('Pants', ['foo'=>2, 'bar'=>1]);
        $result = $d->manager->getById('Pants', array(2, 1));
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
        $d = Test\Factory::managerArraysModelCustom([
            'Pants'=>[
                'class'   => 'stdClass',
                'primary' => ['foo', 'bar'],
                'fields'  => ['foo'=>true, 'bar'=>true],
            ],
        ]);
        $d->manager->insertTable('Pants', ['foo'=>2, 'bar'=>1]);
        $result = $d->manager->getById('Pants', ['bar'=>1, 'foo'=>2]);
        $this->assertEquals(2, $result->foo);
        $this->assertEquals(1, $result->bar);
    }
}
