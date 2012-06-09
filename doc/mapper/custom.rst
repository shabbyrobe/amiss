Custom Mapping
==============

.. _custom-mapping:

Creating your own mapper
------------------------

If none of the available mapping options are suitable, you can always roll your own by subclassing ``Amiss\Mapper\Base``, or if you're really hardcore (and don't want to use any of the help provided by the base class), by implementing the ``Amiss\Mapper`` interface.

Both methods require you to build up an instance of ``Amiss\Meta``, which defines various object-mapping attributes that ``Amiss\Manager`` will make use of.

.. note:: You should be familiar with the structure of the :doc:`metadata` before reading this guide.


Extending ``Amiss\Mapper\Base``
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

``Amiss\Mapper\Base`` requires you to implement one method:

.. py:function:: protected createMeta( $class )

    Must return an instance of ``Amiss\Meta`` for the ``$class``. See :doc:`metadata` for details on how to structure this object.

    :param class: The class name to create the Meta object for. This will already have been resolved using ``resolveObjectName`` (see below).


You can also use the following methods to help write your ``createMeta`` method, or extend them to tweak your mapper's behaviour:

.. py:function:: protected resolveObjectName( $name )

    Take a name provided to ``Amiss\Manager`` and convert it before it gets passed to ``createMeta``.


.. py:function:: protected getDefaultTable( $class )

    When no table is specified, you can use this method to generate a table name based on the class name. By default, it will take a ``Class\Name\Like\ThisOne`` and make a table name like ``this_one``.


.. py:function:: protected resolveUnnamedFields( $fields )

    If you want to make use of the base mapper's facilities for naming fields that are not explicitly named in the mapping configuration, pass an array of field definitions and the name property will be assigned. The updated field list is then returned.


Implementing ``Amiss\Mapper``
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Taking this route implies that you want to take full control of the object creation and row export process, and want nothing to do with the help that ``Amiss\Mapper\Base`` can offer you. 

The following functions must be implemented:

.. py:function:: getMeta ( $class )
    
    Must return an instance of ``Amiss\Meta`` that defines the mapping for the class name passed. See :doc:`metadata` for details on how to structure this object.

    :param class: A string containing the name used when ``Amiss\Manager`` is called to act on an "object".


.. py:function:: fromObject ( $meta , $object )
    
    Creates a row that will be used to insert or update the database. Must return a 1-dimensional associative array (or instance of `ArrayAccess <http://php.net/manual/en/class.arrayaccess.php>`_).

    :param meta:    ``Amiss\Meta`` defining the mapping
    :param object:  The object containing the values which will be used for the row


.. py:function:: toObject ( $meta , $object , $args )
    
    Create the object mapped by the passed ``Amiss\Meta`` object, assign the values from the ``$row``, and return the freshly minted instance.

    :param meta:    ``Amiss\Meta`` defining the mapping
    :param object:  The object containing the values which will be used for the row


.. py:function:: createObject ( $meta , $input , array $args = null )

    Create the object mapped by the passed ``Amiss\Meta`` object. It is acceptable to glean 
    constructor arguments from the ``$row``, but properties should not be assigned from the row:
    that's ``populateObject``'s job.

    Constructor arguments are passed using ``$args``, but if you really have to, you can ignore them. Or merge them 
    with an existing array. Or whatever.
	
    :param meta:  ``Amiss\Meta`` defining the mapping
    :param row:   Database row to use when populating your instance
    :param args:  Array of constructor arguments passed to ``Amiss\Manager``. Will most likely be empty.


.. py:function:: createObject ( $meta , $object , $input )

	Use the information in ``$meta`` to decide how to assign the values from ``$input`` to ``$object``. 


.. py:function:: determineTypeHandler ( $type )

    Return an instance of ``Amiss\Type\Handler`` for the passed type. Can return ``null``.

    This is only really used by the ``Amiss\TableBuilder`` class when you roll your own mapper unless you make use of it yourself in ``fromObject`` and ``toObject``. If you don't intend to use the table builer and don't intend to use this facility to map types yourself, just leave the method body empty.

    :param type:  The ID of the type to return a handler for.


.. _custom-type-handler:

Creating your own type handler
------------------------------

To create your own type handler, you need to implement the ``Amiss\Type\Handler`` interface. This interface requires three methods:

.. py:function:: prepareValueForDb( $value , $object , array $fieldInfo)
    
    Take an object value and prepare it for insertion into the database
    

.. py:function:: handleValueFromDb( $value )
    
    Takes a value coming out of the database and prepare it for assigning to an object.


.. py:function:: createColumnType( $engine )

    This generates the database type string for use in table creation. See :doc:`/schema` for more info. You can simply leave this method empty if you prefer and the type declared against the field will used instead if it is set.

    This method makes the database engine name available so you can return a different type depending on whether you're using MySQL or SQLite.


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


To make use of your new handler, declare an object with fields that map to your handler's ID and register the handler with your mapper:

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

.. code-block:: php

    <?php
    $f = $manager->getById('Foo', 1);
    var_dump($f->bar);
    

In the situation where you want to handle a specific database type (like ``DATETIME`` or ``VARCHAR``), you can provide a handler for it and simply leave the ``createColumnType`` method body empty. 

To determine the id for the handler to use, it takes everything up to the first space or opening parenthesis. In the following example, the type handler ``varchar`` will be used for column ``bar``:

.. code-block:: php

    <?php
    class Foo
    {
        /**
         * @field
         * @type VARCHAR(48)
         */
        public $bar;
    }
    $mapper->addTypeHandler(new BlahBlahHandler, 'varchar');

.. note:: Handler ids are case insensitive.
