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

    function testMetaBeforeInsert()
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
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $o = $nm->getList('Pants');
        $this->assertEquals(249, $o[0]->foo);
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
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $this->assertEquals(249, $o->foo);
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
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->id = 1;
        $o->foo = 123;
        $nm->insert($o, $nm->getMeta('Pants'));
        $this->assertEquals(123, $o->foo);

        $o = $nm->getById($o, 1);
        $this->assertEquals(123, $o->foo);
        $nm->update($o, $nm->getMeta('Pants'));
        $this->assertEquals(249, $o->foo);

        $o = $nm->getById($o, 1);
        $this->assertEquals(249, $o->foo);
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
        $this->assertEquals(249, $o->foo);

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
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->id = 1;
        $nm->insert($o, $nm->getMeta('Pants'));

        // delete
        $o = $nm->getById($o, 1);
        $this->assertEquals(1, $o->id);
        $nm->delete($o);

        // It will change in the instance
        $this->assertEquals(99, $o->id);

        // It should still exist in the DB because we should have
        // tried to delete the wrong ID. God help you if you ever
        // do this in real life.
        $o = $nm->getById($o, 1);
        $this->assertEquals(1, $o->id);
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
        $cls = "$ns\\Pants";
        $o = new $cls;
        $o->id = 1;
        $nm->insert($o, $nm->getMeta('Pants'));

        // delete
        $o = $nm->getById($o, 1);
        $this->assertEquals(1, $o->id);
        $nm->delete($o);

        // It will change in the instance
        $this->assertEquals(99, $o->id);

        // It should not exist in the DB
        $o = $nm->getById($o, 1);
        $this->assertNull($o);
    }
}
