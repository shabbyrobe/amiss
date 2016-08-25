<?php
namespace Amiss\Test\Acceptance\Manager;

use Amiss\Sql\Manager;
use Amiss\Test;

class EventTest extends \Amiss\Test\Helper\TestCase
{
    function testBeforeSave()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /** :amiss = {"on": {"beforeSave": ["a", "b"]}}; */
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $foo;

                function a() { $this->foo = $this->foo * 2; }
                function b() { $this->foo = $this->foo + 3; }
            }
        ');
        $d->manager->on['beforeSave'][] = function($object) {
            $object->foo -= 5;
        };
        $result = ((123 * 2) + 3) - 5; // 244
        $cls = $d->classes['Pants'];
        $o = new $cls;
        $o->foo = 123;
        $d->manager->save($o, $d->manager->getMeta($cls));
        $o = $d->manager->getList($cls);
        $this->assertEquals($result, $o[0]->foo);
    }

    function testMetaAfterSave()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /** :amiss = {"on": {"afterSave": ["a", "b"]}}; */
            class Pants {
                /** :amiss = {"field": {"primary": true}}; */
                public $foo;

                function a() { $this->foo = $this->foo * 2; }
                function b() { $this->foo = $this->foo + 3; }
            }
        ');
        $d->manager->on['afterSave'][] = function($object) {
            $object->foo -= 5;
        };
        $cls = $d->classes['Pants'];
        $o = new $cls;
        $o->foo = 123;
        $d->manager->save($o, $d->manager->getMeta($cls));
        $this->assertEquals(244, $o->foo);
    }

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
        $cls = $d->classes['Pants'];
        $o = new $cls;
        $o->foo = 123;
        $d->manager->insert($o, $d->manager->getMeta($cls));
        $o = $d->manager->getList($cls);
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
        $cls = $d->classes['Pants'];
        $o = new $cls;
        $o->foo = 123;
        $d->manager->insert($o, $d->manager->getMeta($cls));
        $this->assertEquals(244, $o->foo);
        $o = $d->manager->getList($cls);
        $this->assertEquals(123, $o[0]->foo);
    }

    function testMetaBeforeUpdate()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /**
             * :amiss = {"on": {"beforeUpdate": ["a", "b"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
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

        $cls = $d->classes['Pants'];
        $o = new $cls;
        $o->id = 1;
        $o->foo = 123;
        $d->manager->insert($o, $d->manager->getMeta($cls));
        $this->assertEquals(123, $o->foo);

        $o = $d->manager->getById($cls, 1);
        $this->assertEquals(123, $o->foo);
        $d->manager->update($o, $d->manager->getMeta($cls));
        $this->assertEquals(244, $o->foo);

        $o = $d->manager->getById($cls, 1);
        $this->assertEquals(244, $o->foo);
    }

    function testMetaAfterUpdate()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /**
             * :amiss = {"on": {"afterUpdate": ["a", "b"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
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

        $cls = $d->classes['Pants'];
        $o = new $cls;
        $o->id = 1;
        $o->foo = 123;
        $d->manager->insert($o, $d->manager->getMeta($cls));
        $this->assertEquals(123, $o->foo);

        // update
        $o = $d->manager->getById($cls, 1);
        $this->assertEquals(123, $o->foo);
        $d->manager->update($o, $d->manager->getMeta($cls));

        // it will change in the instance
        $this->assertEquals(244, $o->foo);

        // It will not have changed in the DB
        $o = $d->manager->getById($cls, 1);
        $this->assertEquals(123, $o->foo);
    }

    function testMetaBeforeDelete()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /**
             * :amiss = {"on": {"beforeDelete": ["a"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
                public $id;

                function a() { $this->id = 99; }
            }
        ');
        $cls = $d->classes['Pants'];

        $d->manager->insertTable($cls, ['id'=>94]);
        $d->manager->on['beforeDelete'][] = function($object) {
            $object->id -= 5;
        };

        $o = new $cls;
        $o->id = 1;
        $d->manager->insert($o, $d->manager->getMeta($cls));

        // delete
        $o = $d->manager->getById($cls, 1);
        $this->assertEquals(1, $o->id);
        $d->manager->delete($o);

        // It will change in the instance
        $this->assertEquals(94, $o->id);

        // It should still exist in the DB because we should have
        // tried to delete the wrong ID. God help you if you ever
        // do this in real life.
        $o = $d->manager->getById($cls, 1);
        $this->assertEquals(1, $o->id);

        // but the other one shouldn't
        $this->assertFalse($d->manager->exists($cls, 94));
    }

    function testMetaAfterDelete()
    {
        $d = Test\Factory::managerNoteModelCustom('
            /**
             * :amiss = {"on": {"afterDelete": ["a"]}};
             */
            class Pants {
                /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
                public $id;

                function a() { $this->id = 99; }
            }
        ');
        $cls = $d->classes['Pants'];

        $d->manager->insertTable($cls, ['id'=>94]);
        $d->manager->on['afterDelete'][] = function($object) {
            $object->id -= 5;
        };

        $o = new $cls;
        $o->id = 1;
        $d->manager->insert($o, $d->manager->getMeta($cls));

        // delete
        $o = $d->manager->getById($cls, 1);
        $this->assertEquals(1, $o->id);
        $d->manager->delete($o);

        // It will change in the instance
        $this->assertEquals(94, $o->id);

        // It should not exist in the DB
        $o = $d->manager->getById($cls, 1);
        $this->assertNull($o);

        // but the other one should
        $this->assertTrue($d->manager->exists($cls, 94));
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
