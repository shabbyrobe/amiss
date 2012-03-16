Static Mapper
=============

.. note::   It is assumed by this mapper that an object and a table are corresponding entities. 
            More complex mapping should be handled using a custom mapper.


If you're a Yii ActiveRecord refugee (or, bog help you, PRADO), this might help ease your migration path. It's a desecration of the sanctity of your model objects, but you know the law... "you gotta do what you gotta do".


Table Mapping
-------------

By default, the table name will be derived from the object. If you want the object to explicitly declare the table to which it refers, specify a static field called ``table``:

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
