<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Sql\Manager;
use Amiss\Test;

class EventTest extends \Amiss\Test\Helper\TestCase
{
    function testBeforeInsert()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /** :amiss = {"on": {"beforeInsert": ["a", "b"]}}; */
            class Pants {
                /** :amiss = {"field": true}; */
                public $foo;

                function a() { $this->foo = $this->foo * 2; }
                function b() { $this->foo = $this->foo + 3; }
            }
        ');
        $d->manager->on['beforeInsert'][] = function($object) {
            $object->foo -= 5;
        };
        $result = ((123 * 2) + 3) - 5; // 244
        $cls = "{$d->ns}\\Pants";
        $o = new $cls;
        $o->foo = 123;
        $d->manager->insert($o, $d->manager->getMeta('Pants'));
        $o = $d->manager->getList('Pants');
        $this->assertEquals($result, $o[0]->foo);
    }

    function testMetaAfterInsert()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /** :amiss = {"on": {"afterInsert": ["a", "b"]}}; */
            class Pants {
                /** :amiss = {"field": true}; */
                public $foo;

                function a() { $this->foo = $this->foo * 2; }
                function b() { $this->foo = $this->foo + 3; }
            }
        ');
        $d->manager->on['afterInsert'][] = function($object) {
            $object->foo -= 5;
        };
        $cls = "{$d->ns}\\Pants";
        $o = new $cls;
        $o->foo = 123;
        $d->manager->insert($o, $d->manager->getMeta('Pants'));
        $this->assertEquals(244, $o->foo);
        $o = $d->manager->getList('Pants');
        $this->assertEquals(123, $o[0]->foo);
    }

    function testMetaBeforeUpdate()
    {
        $d = Test\Factory::managerNoteModelCustom('
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
        $d->manager->on['beforeUpdate'][] = function($object) {
            $object->foo -= 5;
        };

        $cls = "{$d->ns}\\Pants";
        $o = new $cls;
        $o->id = 1;
        $o->foo = 123;
        $d->manager->insert($o, $d->manager->getMeta('Pants'));
        $this->assertEquals(123, $o->foo);

        $o = $d->manager->getById($o, 1);
        $this->assertEquals(123, $o->foo);
        $d->manager->update($o, $d->manager->getMeta('Pants'));
        $this->assertEquals(244, $o->foo);

        $o = $d->manager->getById($o, 1);
        $this->assertEquals(244, $o->foo);
    }

    function testMetaAfterUpdate()
    {
        $d = Test\Factory::managerNoteModelCustom('
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
        $d->manager->on['afterUpdate'][] = function($object) {
            $object->foo -= 5;
        };

        $cls = "{$d->ns}\\Pants";
        $o = new $cls;
        $o->id = 1;
        $o->foo = 123;
        $d->manager->insert($o, $d->manager->getMeta('Pants'));
        $this->assertEquals(123, $o->foo);

        // update
        $o = $d->manager->getById($o, 1);
        $this->assertEquals(123, $o->foo);
        $d->manager->update($o, $d->manager->getMeta('Pants'));

        // it will change in the instance
        $this->assertEquals(244, $o->foo);

        // It will not have changed in the DB
        $o = $d->manager->getById($o, 1);
        $this->assertEquals(123, $o->foo);
    }

    function testMetaBeforeDelete()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /**
             * :amiss = {"on": {"beforeDelete": ["a"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                function a() { $this->id = 99; }
            }
        ');
        $d->manager->insertTable('Pants', ['id'=>94]);
        $d->manager->on['beforeDelete'][] = function($object) {
            $object->id -= 5;
        };

        $cls = "{$d->ns}\\Pants";
        $o = new $cls;
        $o->id = 1;
        $d->manager->insert($o, $d->manager->getMeta('Pants'));

        // delete
        $o = $d->manager->getById($o, 1);
        $this->assertEquals(1, $o->id);
        $d->manager->delete($o);

        // It will change in the instance
        $this->assertEquals(94, $o->id);

        // It should still exist in the DB because we should have
        // tried to delete the wrong ID. God help you if you ever
        // do this in real life.
        $o = $d->manager->getById($o, 1);
        $this->assertEquals(1, $o->id);

        // but the other one shouldn't
        $this->assertFalse($d->manager->exists('Pants', 94));
    }

    function testMetaAfterDelete()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /**
             * :amiss = {"on": {"afterDelete": ["a"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $id;

                function a() { $this->id = 99; }
            }
        ');
        $d->manager->insertTable('Pants', ['id'=>94]);
        $d->manager->on['afterDelete'][] = function($object) {
            $object->id -= 5;
        };

        $cls = "{$d->ns}\\Pants";
        $o = new $cls;
        $o->id = 1;
        $d->manager->insert($o, $d->manager->getMeta('Pants'));

        // delete
        $o = $d->manager->getById($o, 1);
        $this->assertEquals(1, $o->id);
        $d->manager->delete($o);

        // It will change in the instance
        $this->assertEquals(94, $o->id);

        // It should not exist in the DB
        $o = $d->manager->getById($o, 1);
        $this->assertNull($o);

        // but the other one should
        $this->assertTrue($d->manager->exists('Pants', 94));
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
