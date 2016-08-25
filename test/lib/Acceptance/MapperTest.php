<?php
namespace Amiss\Test\Acceptance;

use Amiss\Test;
use Amiss\Test\Helper\ClassBuilder;

/**
 * @group acceptance
 * @group mapper
 */
class MapperTest extends \Amiss\Test\Helper\TestCase
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

    public function testMapRowToObjectMeta()
    {
        $mapper = $this->deps->mapper;
        $obj = $mapper->mapRowToObject(\Amiss\Demo\Artist::class, ['artistId'=>1]);
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);

        $obj = $mapper->mapRowToObject($mapper->getMeta('Amiss\Demo\Artist'), ['artistId'=>1]);
        $this->assertInstanceOf('Amiss\Demo\Artist', $obj);
    }

    public function testMapObjectToRowMeta()
    {
        $mapper = $this->deps->mapper;
        $a = new \Amiss\Demo\Artist();
        $a->artistId = 1;
        $array = $mapper->mapObjectToRow($a, 'Amiss\Demo\Artist');
        $this->assertInternalType('array', $array);

        $array = $mapper->mapObjectToRow($a, $mapper->getMeta('Amiss\Demo\Artist'));
        $this->assertInternalType('array', $array);
    }

    public function testObjectToProperties()
    {
        $deps = Test\Factory::managerArraysModelCustom([
            'Pants'=>[
                'class'   => 'stdClass',
                'fields'  => [
                    'id'=>['name'=>'i_d'],
                    'yep'=>['name'=>'y_e_p'],
                    'otre'=>['name'=>'o_t_r_e'],
                ],
            ],
        ]);
        $array = [
            'id'=>1,
            'yep'=>'hello',
            'otre'=>(object)['hello'=>'world'],
        ];
        $object = (object)$array;
        $meta = $deps->mapper->getMeta('Pants');
        $props = $deps->mapper->mapObjectToProperties($object, $meta);
        $this->assertEquals($array, $props);
    }

    public function testObjectToPropertiesWithGetters()
    {
        $deps = Test\Factory::managerNoteModelCustom('
            /** :amiss = true; */
            class Pants {
                /** :amiss = {"field": true}; */
                function getId() { return $this->id; }
                function setId($v) { $this->id = $v; }
            }
        ');
        $c = $deps->ns."\Pants";
        $pants = new $c;
        $pants->setId(1);

        $props = $deps->mapper->mapObjectToProperties($pants);
        $this->assertEquals(['id'=>1], $props);
    }
}
