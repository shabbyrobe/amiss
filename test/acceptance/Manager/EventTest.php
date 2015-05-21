<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Sql\Manager;

class EventTest extends \CustomMapperTestCase
{
    private $callback;

    function doEvent(...$args)
    {
        $cb = $this->callback;
        $cb(...$args);
    }

    function testBeforeInsert()
    {
        list ($nm, $ns) = $this->createDefaultNoteManager('
            /**
             * :amiss = {
             *     "on": {
             *         "beforeInsert": ["a", "b"]
             *     }
             * };
             */
            class Pants {
                /** :amiss = {"field": true}; */
                public $foo;

                function a() {
                    $this->foo = $this->foo * 2;
                }
                function b() {
                    $this->foo = $this->foo + 3;
                }
            }
        ');
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $o = $nm->getList('Pants');
        $this->assertEquals(249, $o[0]->foo);
    }

    function testAfterInsert()
    {
        list ($nm, $ns) = $this->createDefaultNoteManager('
            /**
             * :amiss = {
             *     "on": {
             *         "afterInsert": ["a", "b"]
             *     }
             * };
             */
            class Pants {
                /** :amiss = {"field": true}; */
                public $foo;

                function a() {
                    $this->foo = $this->foo * 2;
                }
                function b() {
                    $this->foo = $this->foo + 3;
                }
            }
        ');
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $this->assertEquals(249, $o->foo);
        $o = $nm->getList('Pants');
        $this->assertEquals(123, $o[0]->foo);
    }
}
