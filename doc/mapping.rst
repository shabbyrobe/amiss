Mapping
=======

Using Annotations
-----------------

To use an annotation mapper with Amiss, pass an instance of ``Amiss\Mapper\Note`` to ``Amiss\Manager``::

.. code-block:: php

    <?php
    $mapper = new \Amiss\Note\Mapper;
    $manager = new \Amiss\Manager($db, $mapper);


You are then 



Using Static Properties
-----------------------

If you're a Yii ActiveRecord refugee (or, bog help you, PRADO), this might help ease your migration path. It's a desecration of the sanctity of your model objects, but you know the law... "you gotta do what you gotta do".

So this type of mapper is really mostly there for the sake of :doc:`active/index`, which we'll get to later.


Help me your mappings suck I want to do it my own way
-----------------------------------------------------



Common Mapper Configuration
---------------------------




It is assumed that an object and a table are corresponding entities. More complex mapping is outside the scope of what Amiss intends to provide.


Default Mapping Method
----------------------

Amiss does not require that you specify any table or property/field mappings. It does not introspect your database's schema and it does not check your class for annotations. In order to determine what table and field names to use when querying, it has to guess.


Table Mapping
~~~~~~~~~~~~~

Words in the object's name are ``PascalCased``. Words in the table's name will be ``underscore_separated``. Amiss will handle this translation.

This default can be disabled using the following property:

.. py:attribute:: Amiss\\Manager->convertTableNames
    
    Defaults to ``true``. Set to ``false`` if your table's names will be exactly the same as your objects.


At the moment, it is only possible for an instance of ``Amiss\Manager`` to manage one model namespace, unless you want to write the full namespace for every operation:

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    class Foo { public $id; }

    $manager = ...;
    $manager->get('Amiss\Demo\Foo', 1);
    $manager->objectNamespace = 'Amiss\Demo';
    $manager->get('Foo', 1);


Property Mapping
~~~~~~~~~~~~~~~~

When relying on the default mapping, the object's fields have the same name as the table's columns.

The following property can help tweak this behaviour:

.. py:attribute:: Amiss\\Manager->convertFieldUnderscores

    Defaults to ``false``. Set to ``true`` if your table's field names are ``underscore_separated``, but your object's properties are ``camelCased``


When selecting an object, Amiss will simply assume that each field has a correspondingly named property in the object (taking into account the underscores issue mentioned above if enabled).

Inserting and updating by object will enumerate all publicly accessible properties of the object that **aren't an array, an object or null** and *assume they are a column to be saved*:

.. code-block:: php

    <?php
    class FooBar
    {
        // explicit scalar value will be assumed to be a column
        public $yep1='yep';

        // same as above
        public $yep2=2;

        // false !== null, so this is considered a column value
        public $yep3=false;

        // public properties are null by default, so this is skipped
        public $nope1;

        // let's put an array in here later. it won't be considered.
        public $nope2;

        // let's put an object in here later. it won't be considered.
        public $nope3;

        // explicitly null public property, not considered a column
        public $nope4=null;

        // protected properties are not accessible to a foreach loop over an object, 
        // so it is not considered a column value
        protected $nope3='nope';

        // see protected property
        private $nope4='nope';
    }

    $fb = new FooBar;
    $fb->nope2 = array('a', 'b');
    $fb->nope3 = new stdClass;
    $manager->insert($fb);

    // will generate the following statement:
    // INSERT INTO foo_bar(yep1, yep2, yep3) VALUES(:yep1, :yep2, :yep3)


The rationale for this is as follows:

* Objects are skipped because they are assumed to belong to relations, and should be saved separately
* Arrays have no 1 to 1 representation in MySQL that isn't platform agnostic, and are also likely to represent 1-to-n relations (as in ``Event->eventArtists``)
* An object with a property representing a relation will have a null value if there is no related object, but there will be no field in the database. 

.. warning:: There is a potentially serious gotcha documented here: :ref:`null-handling`


Custom Mapping
--------------

In spite of the :ref:`null-handling`, the default behaviour will work well in quite a lot of situations. 

In the event that it doesn't, there are options:


Name Mappers
~~~~~~~~~~~~

If your object/table or property/field mappings are not quite able to be managed by the defaults but a simple function would do the trick (for example, you are working with a database that has no underscores in its table names, or you have a bizarre preference for sticking ``m_`` at the start of every one of your object properties), you can use a simple name mapper to do the job for you using the following properties:

.. py:attribute:: Amiss\\Manager->objectToTableMapper
    
    Converts an object name to a table name. This property accepts either a PHP :term:`callback` type or an instance of ``Amiss\Name\Mapper``, although in the latter case, only the ``to()`` method will ever be used.


.. py:attribute:: Amiss\\Manager->propertyColumnMapper
    
    Converts a property name to a database column name and vice-versa. This property *only* accepts an instance of ``Amiss\Name\Mapper``. It uses the ``to()`` method to convert a property name to a column name, and the ``from()`` method to convert a column name back to a property name.



Bugger This, I'll Do It Myself!
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Amiss allows you to manually specify table names for objects. The object name **must** contain the namespace.

.. code-block:: php

    <?php
    $manager = new Amiss\Manager(...);
    $manager->tableMap['My\Object'] = 'some_weirdo_TNAME';


Amiss provides two interfaces for custom property/field mapping:

.. py:class:: interface Amiss\\RowExporter

    .. py:method:: exportRow()

    Handles converting an object's properties into an array that represents the row. Array keys should *exactly* match the field names.

.. py:class:: interface Amiss\\RowBuilder

    .. py:method:: buildObject(array $row)

    Handles assigning the row's values to the object's properties.


.. code-block:: php

    <?php
    class FooBar implements Amiss\RowExporter, Amiss\RowBuilder
    {
        public $name;
        public $anObject;
        public $setNull;
        
        public function exportRow()
        {
            $values = (array)$this;
            $values['anObject'] = serialize($values['anObject']);
            return $values;
        }

        public function buildObject(array $row)
        {
            $this->name = $row['name'];
            $this->anObject = unserialize($row['anObject']);
            $this->setNull = $row['setNull'];
        }
    }
    $fb = new FooBar();
    $fb->anObject = new stdClass;
    $manager->insert($fb);


In the above example, ``exportRow()`` will be called by ``Amiss\Manager`` in order to get the values to use in the ``INSERT`` query, completely bypassing the default row export.

I can hear you screaming: "Get your damn hands off my model". I agree. But it could be worse for a domain-model purist: it could be one of those pesky :doc:`/active/index`, rather than a relatively unobtrusive interface. Besides, such purism would be far better served by `Doctrine <http://www.doctrine-project.org/>`_.






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


