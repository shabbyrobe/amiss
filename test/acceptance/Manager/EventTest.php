<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Sql\Manager;

class EventTest extends \CustomMapperTestCase
{
    function testBeforeInsert()
    {
        list ($nm, $ns) = $this->createDefaultNoteManager('
            /**
             * :amiss = {"on": {"beforeInsert": ["a", "b"]}};
             */
            class Pants {
                /** :amiss = {"field": true}; */
                public $foo;

                function a() { $this->foo = $this->foo * 2; }
                function b() { $this->foo = $this->foo + 3; }
            }
        ');
        $nm->on['beforeInsert'][] = function($object) {
            $object->foo -= 5;
        };
        $result = ((123 * 2) + 3) - 5; // 244
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $o = $nm->getList('Pants');
        $this->assertEquals($result, $o[0]->foo);
    }

    function testMetaAfterInsert()
    {
        list ($nm, $ns) = $this->createDefaultNoteManager('
            /**
             * :amiss = {"on": {"afterInsert": ["a", "b"]}};
             */
            class Pants {
                /** :amiss = {"field": true}; */
                public $foo;

                function a() { $this->foo = $this->foo * 2; }
                function b() { $this->foo = $this->foo + 3; }
            }
        ');
        $nm->on['afterInsert'][] = function($object) {
            $object->foo -= 5;
        };
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $this->assertEquals(244, $o->foo);
        $o = $nm->getList('Pants');
        $this->assertEquals(123, $o[0]->foo);
    }

    function testMetaBeforeUpdate()
    {
        list ($nm, $ns) = $this->createDefaultNoteManager('
            /**
             * :amiss = {"on": {"beforeUpdate": ["a", "b"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                /** :amiss = {"field": true}; */
                public $foo;

                function a() { $this->foo = $this->foo * 2; }
                function b() { $this->foo = $this->foo + 3; }
            }
        ');
        $nm->on['beforeUpdate'][] = function($object) {
            $object->foo -= 5;
        };

        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->id = 1;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $this->assertEquals(123, $o->foo);

        $o = $nm->getById($o, 1);
        $this->assertEquals(123, $o->foo);
        $nm->update($o, $nm->getMeta('Pants'));
        $this->assertEquals(244, $o->foo);

        $o = $nm->getById($o, 1);
        $this->assertEquals(244, $o->foo);
    }

    function testMetaAfterUpdate()
    {
        list ($nm, $ns) = $this->createDefaultNoteManager('
            /**
             * :amiss = {"on": {"afterUpdate": ["a", "b"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                /** :amiss = {"field": true}; */
                public $foo;

                function a() { $this->foo = $this->foo * 2; }
                function b() { $this->foo = $this->foo + 3; }
            }
        ');
        $nm->on['afterUpdate'][] = function($object) {
            $object->foo -= 5;
        };

        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->id = 1;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $this->assertEquals(123, $o->foo);

        // update
        $o = $nm->getById($o, 1);
        $this->assertEquals(123, $o->foo);
        $nm->update($o, $nm->getMeta('Pants'));

        // it will change in the instance
        $this->assertEquals(244, $o->foo);

        // It will not have changed in the DB
        $o = $nm->getById($o, 1);
        $this->assertEquals(123, $o->foo);
    }

    function testMetaBeforeDelete()
    {
        list ($nm, $ns) = $this->createDefaultNoteManager('
            /**
             * :amiss = {"on": {"beforeDelete": ["a"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                function a() { $this->id = 99; }
            }
        ');
        $nm->insertTable('Pants', ['id'=>94]);
        $nm->on['beforeDelete'][] = function($object) {
            $object->id -= 5;
        };

        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->id = 1;
        $nm->insert($o, $nm->getMeta('Pants'));

        // delete
        $o = $nm->getById($o, 1);
        $this->assertEquals(1, $o->id);
        $nm->delete($o);

        // It will change in the instance
        $this->assertEquals(94, $o->id);

        // It should still exist in the DB because we should have
        // tried to delete the wrong ID. God help you if you ever
        // do this in real life.
        $o = $nm->getById($o, 1);
        $this->assertEquals(1, $o->id);

        // but the other one shouldn't
        $this->assertFalse($nm->exists('Pants', 94));
    }

    function testMetaAfterDelete()
    {
        list ($nm, $ns) = $this->createDefaultNoteManager('
            /**
             * :amiss = {"on": {"afterDelete": ["a"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                function a() { $this->id = 99; }
            }
        ');
        $nm->insertTable('Pants', ['id'=>94]);
        $nm->on['afterDelete'][] = function($object) {
            $object->id -= 5;
        };

        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->id = 1;
        $nm->insert($o, $nm->getMeta('Pants'));

        // delete
        $o = $nm->getById($o, 1);
        $this->assertEquals(1, $o->id);
        $nm->delete($o);

        // It will change in the instance
        $this->assertEquals(94, $o->id);

        // It should not exist in the DB
        $o = $nm->getById($o, 1);
        $this->assertNull($o);

        // but the other one should
        $this->assertTrue($nm->exists('Pants', 94));
    }

    function testMetaEventFailsWhenClosure()
    {
        $this->setExpectedException(\Amiss\Exception::class);
        $meta = new \Amiss\Meta('stdClass', [
            'on'=>[
                'beforeUpdate'=>[function() {}],
            ],
        ]);
    }
}
