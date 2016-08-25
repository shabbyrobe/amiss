Custom Mapping
==============

.. _custom-mapping:

Creating your own mapper
------------------------

If none of the available mapping options are suitable, you can always roll your
own by subclassing ``Amiss\Mapper\Base``, or if you're really hardcore (and
don't want to use any of the help provided by the base class), by implementing
the ``Amiss\Mapper`` interface.

Both methods require you to build up an instance of ``Amiss\Meta``, which
defines various object- mapping attributes that ``Amiss\Sql\Manager`` will make
use of.

.. note:: You should be familiar with the structure of the :doc:`metadata`
   before reading this guide


Extending ``Amiss\Mapper\Base``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``Amiss\Mapper\Base`` requires you to implement one method:

``protected createMeta( $class )``

    Must return an instance of ``Amiss\Meta`` for the ``$class``. See
    :doc:`metadata` for details on how to structure this object.

    ``$class``
        The class name to create the Meta object for. This will already have
        been resolved using ``resolveObjectName`` (see below).


You can also use the following methods to help write your ``createMeta`` method,
or extend them to tweak your mapper's behaviour:

``protected resolveObjectName( $name )``

    Take a name provided to ``Amiss\Sql\Manager`` and convert it before it gets
    passed to ``createMeta``.


``protected getDefaultTable( $class )``

    When no table is specified, you can use this method to generate a table name
    based on the class name. By default, it will take a
    ``Class\Name\Like\ThisOne`` and make a table name like ``this_one``.


``protected resolveUnnamedFields( $fields )``

    If you want to make use of the base mapper's facilities for naming fields
    that are not explicitly named in the mapping configuration, pass an array of
    field definitions and the name property will be assigned. The updated field
    list is then returned.


Implementing ``Amiss\Mapper``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Taking this route implies that you want to take full control of the object
creation and row export process, and want nothing to do with the help that
``Amiss\Mapper\Base`` can offer you.

The following functions must be implemented:

``getMeta ( $id )``
    
    Must return an instance of ``Amiss\Meta`` that defines the mapping for the
    id passed. See :doc:`metadata` for details on how to structure this object.

    ``$id``
        A string containing the name used when ``Amiss\Sql\Manager`` is called
        to act on an "object". May or may not be a class name, depending on the
        Mapper used, but for all simple configurations it will be.


``mapObjectToRow ( $object , $meta = null )``
    
    Creates a row that will be used to insert or update the database. Must
    return a 1-dimensional associative array (or instance of `ArrayAccess
    <http://php.net/manual/en/class.arrayaccess.php>`_).

    ``$object``
        The object containing the values which will be used for the row

    ``$meta``
        ``Amiss\Meta`` defining the mapping. If this isn't passed, it will be
        inferred from the class of the object


``mapRowToObject ( $meta , $object , array $args = null )``
    
    Create the object mapped by the passed ``Amiss\Meta`` object, assign the
    values from the ``$row``, and return the freshly minted instance.

    ``$meta``
        ``Amiss\Meta`` defining the mapping

    ``$object``
        The object containing the values which will be used for the row

    ``$args``
        Optional constructor arguments


``createObject ( $meta , $input , array $args = null )``

    Create the object mapped by the passed ``Amiss\Meta`` object. It is
    acceptable to glean constructor arguments from the ``$row``, but properties
    should not be assigned from the row: that's a job for ``populateObject``.

    Constructor arguments are passed using ``$args``, but if you really have to,
    you can ignore them. Or merge them  with an existing array. Or whatever.
    
    ``$meta``
        ``Amiss\Meta`` defining the mapping :param row:   Database row to use
        when populating your instance :param args:  Array of constructor
        arguments passed to ``Amiss\Sql\Manager``. Will most likely be empty.


``populateObject ( $meta , $object , $input )``

    Use the information in ``$meta`` to decide how to assign the values from
    ``$input`` to ``$object``. 


``determineTypeHandler ( $type )``

    Return an instance of ``Amiss\Type\Handler`` for the passed type. Can return
    ``null``.

    This is only really used by the ``Amiss\Sql\TableBuilder`` class when you
    roll your own mapper unless you make use of it yourself in
    ``mapObjectToRow`` and ``mapRowToObject``. If you don't intend to use the
    table builer and don't intend to use this facility to map types yourself,
    just leave the method body empty.

    ``$type``
        The ID of the type to return a handler for.

