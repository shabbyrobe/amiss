Mapping
=======    

Object mapping with annotations
-------------------------------

Amiss provides a javadoc-style key/value mapper called ``Amiss\Mapper\Note``, which derives from ``Amiss\Mapper\Base``. 

More information is on this mapper is available here:

.. toctree::
    :maxdepth: 1
    
    mapper/annotation


Objects are marked up in this way:

.. code-block:: php

    <?php
    /**
     * @table your_table
     */
    class Foo
    {
        /**
         * @primary
         */
        public $id;

        /**
         * @field some_column
         */
        public $name;
        
        /**
         * @field
         */
        public $barId;

        /**
         * @has many Bar
         */
        public $bars;
    }


Object mapping with static properties
-------------------------------------

This type of mapper is really mostly there for the sake of :doc:`active`, which we'll get to later, but if you prefer this to annotations there's nothing stopping you from using it.

More information is on this mapper is available here:

.. toctree::
    :maxdepth: 1
    
    mapper/statics


.. code-block:: php

    <?php
    
    class Foo
    {
        public static $table = 'your_table';
        public static $fields = array('id', 'name', 'barId');
        public static $primary = 'id';
        public static $relations = array(
            'bars'=>array('one'=>'Bar', 'on'=>'barId');
        );

        public $id;
        public $barId;
        public $name;
    }


.. _mapper-common:

Common Mapper Configuration
---------------------------

Both ``Amiss\Mapper\Note`` and ``Amiss\Mapper\Statics`` derive from ``Amiss\Mapper\Base``. ``Amiss\Mapper\Base`` provides some facilities for making educated guesses about what table name or property names to use when they are not explicitly declared in your mapping configuration.


Name mapping
~~~~~~~~~~~~

If your property/field mappings are not quite able to be managed by the defaults but a simple function would do the trick (for example, you are working with a database that has no underscores in its table names, or you have a bizarre preference for sticking ``m_`` at the start of every one of your object properties), you can use a simple name mapper to do the job for you using the following properties:


.. py:attribute:: Amiss\\Mapper\\Base->objectNamespace

    To save you the trouble of having to declare the full object namespace on every single call to ``Amiss\Manager``, you can configure an ``Amiss\Mapper\Base`` mapper to prepend any object name that is not `fully qualified <http://php.net/namespaces>`_ with one specific namespace by setting this property.

    .. code-block:: php
        
        <?php
        namespace Foo\Bar {
            class Baz {
                public $id;
            }
        }
        namespace {
            $mapper = new Some\Base\Derived\Mapper;
            $mapper->objectNamespace = 'Foo\Bar';
            $manager = new Amiss\Manager($db, $mapper);
            $baz = $manager->getByPk('Baz', 1);

            var_dump(get_class($baz)); 
            // outputs: Foo\Bar\Baz
        }


.. py:attribute:: Amiss\\Mapper\\Base->defaultTableNameMapper
    
    Converts an object name to a table name. This property accepts either a PHP :term:`callback` type or an instance of ``Amiss\Name\Mapper``, although in the latter case, only the ``to()`` method will ever be used.


.. py:attribute:: Amiss\\Manager\\Base->unnamedPropertyMapper
    
    Converts a property name to a database column name and vice-versa. This property *only* accepts an instance of ``Amiss\Name\Mapper``. It uses the ``to()`` method to convert a property name to a column name, and the ``from()`` method to convert a column name back to a property name.


Type Handling
-------------

There's very little intelligence in how Amiss handles values coming in and out of the database. They go in and out of the DB as whatever PDO treats them as by default, which is pretty much always strings or nulls.

This may be fine for 98% of your interaction with the database (trust me - it really will be), but then along come dates and throw a whopping big spanner in the works.

How are you persisting dates? Probably as a YYYY-MM-DD formatted string, yeah? Maybe as a unix timestamp. What about the occasional serialised object?

Amiss active records provide a facility for handling specific database types arbirtrarily.

To create your own type handler, you need to implement the ``Amiss\Type\Handler`` interface.


This interface provides three methods that you need to implement:

.. py:function:: prepareValueForDb(value)
    
    This takes an object value and prepares it for insertion into the database
    

.. py:function:: handleValueFromDb(value)
    
    This takes a value coming out of the database and prepares it for assigning to an object.


.. py:function:: createColumnType(engine)

    This generates the database type string for use in table creation. See :doc:`schema` for more info. You can simply leave this method empty if you prefer and the type declared against the field to be used instead.

    This method makes the database engine available so you can return a different type depending on whether you're using MySQL or Sqlite.


The following (naive) handler demonstrates serialising/deserialising an object into a single column:

.. code-block:: php

    <?php
    class SerialiseHandler implements \Amiss\Type\Handler
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


In order to register this handler with Amiss and allow it to be used, you need to call ``Amiss\Mapper\Base::addTypeHandler($typeHandler)``:

.. code-block:: php

    <?php
    class Foo
    {
        /** @primary */
        public $fooId;

        /**
         * @field
         * @type serialise
         */
        public $bar;

        /**
         * @field
         * @type serialise
         */
        public $baz;
    }

    // anything which derives from Amiss\Mapper\Base will work.
    $mapper = new Amiss\Mapper\Note;
    $mapper->addTypeHandler(new SerialiseHandler(), 'serialise');


Now, when you assign values to those properties, this class will handle the translation between the code and the database:

.. code-block:: php

    <?php
    $f = new Foo();
    $f->bar = (object)array('yep'=>'wahey!');
    $manager->save($f);


The value of ``bar`` in the database will be::

    O:8:"stdClass":1:{s:3:"yep";s:5:"wahey";}


And when we retrieve the object again (assuming a primary key of ``1``), ``bar`` will contain a nicely unserialised ``stdClass`` instance, just like we started with:

    <?php
    $f = $manager->getByPk('Foo', 1);
    var_dump($f->bar);
    

In the situation where you want to handle a specific database type (like ``DATETIME`` or ``VARCHAR``), you can provide a handler for it and simply leave the ``createColumnType`` method body empty. 

To determine the id for the handler to use, it takes everything up to the first space or opening parenthesis. In the following example, the type handler ``varchar`` will be used for column ``bar``:

.. code-block:: php

    <?php
    class Foo extends \Amiss\Active\Record
    {
        /**
         * @field
         * @type VARCHAR(48)
         */
        public $bar;
    }
    $mapper->addTypeHandler(new BlahBlahHandler, 'varchar');

.. note:: Handler ids are case insensitive.


Creating your own mapper
------------------------

If none of the available mapping options are suitable, you can always roll your own by subclassing ``Amiss\Mapper\Base``, or if you're really hardcore (and don't want to use any of the help provided by the base class), by implementing the ``Amiss\Mapper`` interface.

Both methods require you to build an instance of ``Amiss\Meta``, which defines various object-mapping attributes that ``Amiss\Manager`` will make use of.

TODO: document Amiss\Meta.


Extending ``Amiss\Mapper\Base``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``Amiss\Mapper\Base`` requires you to implement one method:

.. py:function:: protected createMeta($class)

    Must return an instance of ``Amiss\Meta``.

    :param class: The class name to create the Meta object for. This will already have been resolved using ``resolveObjectName`` (see below).


You can also use the following methods to help write your ``createMeta`` method, or extend them to tweak your mapper's behaviour:

.. py:function:: protected resolveObjectName($name)

    Take a name provided to ``Amiss\Manager`` and convert it before it gets passed to ``createMeta``.


.. py:function:: protected getDefaultTable($class)

    When no table is specified, you can use this method to generate a table name based on the class name. By default, it will take a ``Class\Name\Like\ThisOne`` and make a table name like ``this_one``.


Implementing ``Amiss\Mapper``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Taking this route implies that you want to take full control of the object creation and row export process, and want nothing to do with the help that ``Amiss\Mapper\Base`` can offer you. 

The following functions must be implemented:

.. py:function:: getMeta($class)
    
    Must return an instance of ``Amiss\Meta`` that defines the mapping for the class name passed.

    :param class: A string containing the name used when ``Amiss\Manager`` is called to act on an "object".


.. py:function:: createObject($meta, $row, $args)

    Create the object mapped by the passed ``Amiss\Meta`` object, assign the values from the ``$row``, and return the freshly minted object.

    Constructor arguments are passed using ``$args``, but if you really have to, you can ignore them. Or merge them with an existing array. Or whatever.

    :param meta:  ``Amiss\Meta`` defining the mapping
    :param row:   Database row to use when populating your instance
    :param args:  Constructor arguments passed to ``Amiss\Manager``. Will most likely be empty.


.. py:function:: exportRow($meta, $object)
    
    Creates a row that will be used to insert or update the database. Must return a 1-dimensional associative array (or instance of ArrayAccess).

    :param meta:    ``Amiss\Meta`` defining the mapping
    :param object:  The object containing the values which will be used for the row


.. py:function:: determineTypeHandler($type)

    Return an instance of ``Amiss\Type\Handler`` for the passed type. Can return ``null``.

    :param type:  The ID of the type to return a handler for.

