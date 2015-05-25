<?php
namespace Amiss\Test\Acceptance;

class LocalMapperTest extends \Amiss\Test\Helper\TestCase
{
    function testLocalMapper()
    {
        $c1 = $this->createFnScopeClass("Pants", "
            class Pants {
                static function meta() {
                    return ['fields'=>[
                        'foo'=>true,
                        'bar'=>true,
                    ]];
                }
            }
        ");
        $lm = new \Amiss\Mapper\Local();
        $meta = $lm->getMeta($c1);
        $this->assertEquals(['foo', 'bar'], array_keys($meta->getFields()));
    }

    function testLocalMapperInherits()
    {
        $ding = $this->createFnScopeClass("Ding", "
            class Ding {
                static function meta() {
                    return [
                        'fields'=>['foo'=>true],
                    ];
                }
            }
        ");
        $dong = $this->createFnScopeClass("Dong", "
            class Dong extends Ding {
                static function meta() {
                    return [
                        'inherit'=>true,
                        'fields'=>['bar'=>true],
                    ];
                }
            }
        ");
        $lm = new \Amiss\Mapper\Local();
        $meta = $lm->getMeta($dong);
        $this->assertEquals(['foo', 'bar'], array_keys($meta->getFields()));
    }

    function testMetaInstance()
    {
        $c1 = $this->createFnScopeClass("Pants", "
            class Pants {
                static function meta() {
                    return new \Amiss\Meta(__CLASS__, ['fields'=>[
                        'foo'=>true,
                    ]]);
                }
            }
        ");
        $lm = new \Amiss\Mapper\Local();
        $meta = $lm->getMeta($c1);
        $this->assertEquals(['foo'], array_keys($meta->getFields()));
        $this->assertEquals($c1, $meta->class);
    }

    function testObjectNamespace()
    {
        $c1 = $this->createFnScopeClass("Pants", "
            class Pants {
                static function meta() {
                    return new \Amiss\Meta(__CLASS__, ['fields'=>[
                        'foo'=>true,
                    ]]);
                }
            }
        ");
        
        $ns = substr($c1, 0, strrpos($c1, '\\'));
        $lm = new \Amiss\Mapper\Local();
        $lm->objectNamespace = $ns;
        $meta = $lm->getMeta("Pants");
        $this->assertEquals(['foo'], array_keys($meta->getFields()));
        $this->assertEquals($c1, $meta->class);
    }

    function testMissingFunction()
    {
        $c1 = $this->createFnScopeClass("Pants", "
            class Pants {}
        ");
        $lm = new \Amiss\Mapper\Local();
        $this->setExpectedException(\UnexpectedValueException::class);
        $meta = $lm->getMeta($c1);
    }

    function testCustomFunction()
    {
        $c1 = $this->createFnScopeClass("Pants", "
            class Pants {
                static function gimmeMeta() {
                    return new \Amiss\Meta(__CLASS__, ['fields'=>['foo'=>true]]);
                }
            }
        ");
        $lm = new \Amiss\Mapper\Local('gimmeMeta');
        $meta = $lm->getMeta($c1);
        $this->assertInstanceOf(\Amiss\Meta::class, $meta);
    }
}
