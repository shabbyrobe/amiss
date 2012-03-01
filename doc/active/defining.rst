Defining
========

At an absolute minimum, all you need to do to create an active record is define an object that contains your field names as properties:

.. code-block:: php
    
    <?php
    namespace Amiss\Demo;
    class Artist extends \Amiss\Active\Record
    {
        public $artistId;
        public $name;
    }


Table Mapping
-------------

By default, the table name will be derived from the object. See the Data Mapper's :doc:`/mapper/mapping` section for more information on this process. If you want the object to explicitly declare the table to which it refers, specify a static field called ``table``:

.. code-block:: php
    
    <?php
    class Artist extends \Amiss\Active\Record
    {
        public static $table = 'artist';
        public $artistId;
        public $name;
    }


Primary Key
-----------

By default, a field with the same name as the object name (namespace excluded) with the "Id" suffix will be assumed to be the primary key. In the case of the following example, the object is called ``Foo``, so it uses the ``fooId`` field as the primary key:

.. code-block:: php

    <?php
    class Foo extends \Amiss\Active\Record
    {
        public $fooId;
        public $name;
    }


.. warning:: Amiss Active Records do not support multi-column primary keys.


If you wish to change the field it uses for the primary key, simply add a static field called ``primary``:

.. code-block:: php
    
    <?php
    class Artist extends \Amiss\Active\Record
    {
        public static $primary = 'thisIsThePrimary';
        public $thisIsThePrimary;
        public $name;
    }


Field Mapping
-------------

Fields can also be defined using the ``Amiss\Active\Record::$fields`` array instead of (or as well as) class properties. This has the advantage of allowing field types to optionally be specified. Each key in ``$fields`` can be used as a virtual property against the object.

.. code-block:: php
    
    <?php
    class Foo extends \Amiss\Active\Record
    {
        public static $fields = array(
            // you don't have to pass the name as the key if there is no value:
            'bar',

            // but you're most welcome to if you prefer the way it looks:
            'baz'=>true,

            // you can also pass a field type:
            'qux'=>'datetime'
        );
    }

    $f = new Foo;
    $f->bar = 'this works';
    echo $f->bar;


.. warning::

    ``Amiss\Active\Record`` derivatives which have their fields declared in this way **are vulnerable** to the :ref:`null-handling` outlined in the Data Mapper's :doc:`/mapper/modifying` documentation. Read on for ways to mitigate this problem.


If you don't specify the types, Amiss will make a guess at what you want them to be. If you're using SQLite, you'll get ``STRING NULL`` columns. If you're using MySQL, you'll get ``VARCHAR(255) NULL`` columns. If this is not what you want, fret not! You can change the default, or you can specify the types on a per-column basis.

Changing the default is done statically at the ``Amiss\Active\Record`` level. You can set it for all ``ActiveRecords``:

.. code-block:: php

    <?php
    Amiss\Active\Record::$defaultFieldType = 'VARCHAR(1024) NOT NULL';


You can set it for specific hierarchies (like the example for multiple connections in the :doc:`connecting` section). In the following example, ``Test1`` and ``Test2`` will use ``INTEGER`` as the column type, but ``Test3`` will use ``VARCHAR(2048)``.

.. code-block:: php

    <?php
    abstract class Base1 extends \Amiss\Active\Record {}
    abstract class Base2 extends \Amiss\Active\Record {}

    class Test1 extends Base1
    {
        public static $fields = array('foo', 'bar');
    }
    
    class Test2 extends Base1
    {
        public static $fields = array('foo', 'bar');
    }
    
    class Test3 extends Base2
    {
        public static $fields = array('foo', 'bar');
    }
    
    Base1::$defaultFieldType = 'INTEGER';
    Base2::$defaultFieldType = 'VARCHAR(2048)';


Or you can set the default on a single ``Amiss\Active\Record`` derivative and it will only apply to that class:

.. code-block:: php

    <?php
    // setting the default as part of the definition
    class Test extends \Amiss\Active\Record
    {
        public static $defaultFieldType = 'VARCHAR(1024) NOT NULL';
        public static $fields = array('foo', 'bar');
    }
    
    // setting the default by hand outside the definition
    Test::$defaultFieldType = 'VARCHAR(2048)';


In the above examples, all of the fields except the primary key (which is not declared in any of the ``$fields`` arrays in the above examples) will be created with the default type. This may not be what you're after - you might also need one property to map to a date column, another to a ``TEXT`` column, etc.

.. note::

    ``Amiss\Active\Record`` derivatives which have their fields declared in this way are **not** vulnerable to the :ref:`null-handling` outlined in the Data Mapper's :doc:`/mapper/mapping` documentation.


By default, the primary key will be created as an autoincrement integer and if ``$primary`` is not set, the name will be inferred from the name of the class. You can override the type of the primary key's column.

When using the default primary key name, simply add a key to the ``$fields`` array with the name of the key as it will be inferred:

.. code-block:: php

    <?php
    class Test extends \Amiss\Active\Record
    {
        public static $fields = array(
            'testId'=>'VARCHAR(1234),
            'foo',
            'bar',
        );
    }


When specifying a key name:

.. code-block:: php

    <?php
    class Test extends \Amiss\Active\Record
    {
        public static $primary = 'fooId',
        public static $fields = array(
            'fooId'=>'VARCHAR(1234),
            'foo',
            'bar',
        );
    }


Type Handling
~~~~~~~~~~~~~

There's very little intelligence in how Amiss handles values coming in and out of the database. They go in and out of the DB as whatever PDO treats them as by default, which is pretty much always strings or nulls.

This may be fine for 98% of your interaction with the database (trust me - it really will be), but then along come dates and throw a whopping big spanner in the works.

How are you persisting dates? Probably as a YYYY-MM-DD formatted string, yeah? Maybe as a unix timestamp. What about the occasional serialised object?

Amiss active records provide a facility for handling specific database types arbirtrarily.

To create your own type handler, you need to implement the ``Amiss\Active\TypeHandler`` interface.


This interface provides three methods that you need to implement:

.. py:function:: prepareValueForDb(value)
    
    This takes an object value and prepares it for insertion into the database
    

.. py:function:: handleValueFromDb(value)
    
    This takes a value coming out of the database and prepares it for assigning to an object.


.. py:function:: createColumnType(engine)

    This generates the database type string for use in table creation. See :doc:`schema` for more info. You can simply leave this method empty if you prefer and the type as declared against the field to be used instead.

    This method makes the database engine available so you can return a different type depending on whether you're using MySQL or Sqlite.


The following (rudimentary) handler demonstrates serialising/deserialising an object into a single column:

.. code-block:: php

    <?php
    class SerialiseHandler implements \Amiss\Active\TypeHandler
    {
        function prepareValueForDb($value)
        {
            return serialize($value);
        }

        function handleValueFromDb($value)
        {
            return unserialize($value);
        }

        function createColumnType($engine)
        {
            return "LONGTEXT";
        }
    }


In order to register this handler with Amiss and allow it to be used, you need to call ``Amiss\Active\Record::addTypeHandler()``:

.. code-block:: php

    <?php
    class Foo extends \Amiss\Active\Record
    {
        public static $fields = array(
            'fooId',
            'bar'=>'serialize',
            'baz'=>'serialize',
        );
    }

    \Amiss\Active\Record::addTypeHandler(new SerialiseHandler(), 'serialize');


Now, when you assign values to those properties, this class will handle the translation between the code and the database:

.. code-block:: php

    <?php
    $f = new Foo();
    $f->bar = (object)array('yep'=>'wahey!');
    $f->save();


The value of ``bar`` in the database will be::

    O:8:"stdClass":1:{s:3:"yep";s:5:"wahey";}


And when we retrieve the object again (assuming a primary key of ``1``), ``bar`` will contain a nicely unserialised ``stdClass`` instance, just like we started with:

    <?php
    $f = Foo::getByPk(`);
    var_dump($f->bar);
    

In the situation where you want to handle a specific database type (like ``DATETIME`` or ``VARCHAR``), you can provide a handler for it and simply leave the ``createColumnType`` method body empty. 

To determine the id for the handler to use, it takes everything up to the first space or opening parenthesis. In the following example, the type handler ``varchar`` will be used for column ``bar``:

.. code-block:: php

    <?php
    class Foo extends \Amiss\Active\Record
    {
        public static $fields = array(
            'bar'=>'VARCHAR(48)',
        );
    }
    Amiss\Active\Record::addTypeHandler(new BlahBlahHandler, 'varchar');

.. note:: Handler ids are case insensitive.


