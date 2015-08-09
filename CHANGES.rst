Changes
=======

v5.0.0
------

Object and table modes for ``update``, ``insert`` and ``delete`` have been
broken into separate methods, ``updateTable``, ``insertTable`` and
``deleteTable`` respectively.

Permissions: ``readOnly`` objects, also ``canUpdate``, ``canDelete``
and ``canInsert``. These are easily bypassed with some fiddling, but they're
a useful guide and safety feature.

Static lookup relator: relations can be defined which retrieve an object from
a static function on another object.

Issue #14 - Order By support for getRelated

``Amiss\Meta`` methods removed:

- ``Meta->getDefaultFieldType()`` becomes ``Meta->fieldType``
- ``Meta->getFields()`` becomes ``Meta->fields``
- ``Meta->getField()`` becomes ``Meta->field[$id]``

Relations can now be automatically populated (with some limits)

Objects can now use properties and auto relations as constructor arguments.

``Amiss\Sql\Manager`` methods which accept positional query parameters
(``foo=?"``) no longer use a variadic for parameter values::

    + $manager->getList(MyModel::class, "pants=? OR pants=?", [1, 2]);
    - $manager->getList(MyModel::class, "pants=? OR pants=?", 1, 2);


Mapper
~~~~~~

The Mapper API has changed significantly.

``Amiss\Mapper\Local`` mapper added: define metadata mappings like
``Amiss\Mapper\Arrays``, but using a static method on the model itself. Defaults
to ``meta``, but this can be changed.

``Amiss\Mapper\Chain`` mapper added: specify a list of mappers to be searched
sequentially for metadata.

``Amiss\Mapper\Base->objectNamespace`` has been removed. The addition of 
``::class`` to the language in PHP 5.5 and Amiss 5's PHP 5.6 requirement 
render it unnecessary. One consequence is that relation definitions using the
Note mapper are now more verbose.

Annotation syntax changed (again) to use the http://github.com/shabbyrobe/nope
library. Migrations can be automated using the ``migrate-notes`` command in
the :ref:`cli`.

``Amiss\Mapper\Note`` now requires a class level ``:amiss`` annotation to exist
in order to map the object, even if that annotation contains no additional
data::

    /** :amiss = true; */
    class Foo {}

``Amiss\Mapper`` is now an interface instead of an abstract class. Common
definitions are extracted into ``Amiss\MapperTrait``.

``Amiss\Mapper`` method signature overhaul::

  +    function createObject($meta, $mapped, $args=null)
  -    function createObject($meta, $row, $args=null)

  +    function mapObjectToRow($object, $meta=null, $context=null)
  -    function fromObject($meta, $input, $context=null)

  +    function mapObjectsToRows($objects, $meta=null, $context=null)
  -    function fromObjects($meta, $input, $context=null)

  +    function populateObject($object, \stdClass $mapped, $meta=null)
  -    function populateObject($meta, $object, $row)

  +    function mapRowToObject($meta, $row, $args=null)
  -    function toObject($meta, $input, $args=null)

  +    function mapRowsToObjects($meta, $rows, $args=null)
  -    function toObjects($meta, $input, $args=null)


v4.2.0
------

- Active Records support ``deleteById`` as a static method.

- ``Mapper->toObject``, ``Mapper->fromObject``, ``Mapper->createObject`` etc
  support accepting a string as well as an instance of ``Amiss\Meta``.

- ``Amiss\Type\Date`` now allows you to specify a subclass of ``DateTime`` to
  use instead of ``DateTime``.

- Moved to packagist


v4.1.0
------

New features
~~~~~~~~~~~~

Static ``assignRelated`` method added to Active Records::

    $records = YourRecord::getList();
    YourRecord::assignRelated($records, 'child');
    
Static constructor support added to mapper, receives unmapped input as argument by default::

    /**
     * @constructor foo
     */
    class YourMappedClass
    {
        static function foo(array $input)
        {
            $c = new static;
            $c->prop = $input['prop'];
            return $c;
        }
    }


v4.0
----

New features:

- Type definitions can pass additional structured metadata
- ``Amiss`` base class added with factory methods for quickly creating managers and mappers with
  all of the prescribed default handlers and relators
- Nested Set extension for SQL manager


``Amiss\Sql\Manager::__construct()`` default handlers/relators removed
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``Amiss\Sql\Manager`` used to assign the ``one``, ``many`` and ``assoc`` relators by default, as
well as the ``date`` and ``autoinc`` type handlers. These have been removed from the constructor
and moved into the ``Amiss`` helper class.

If you make use of these defaults, you will need to change::

	$manager = new Amiss\Sql\Manager($conn);
	
To this::

	$manager = Amiss\Sql\Factory::createManager($conn);


Note mapper relation syntax change
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``Amiss\Mapper\Note`` changes the way relations must be specified. Old-style complex value
declarations like this are no longer used::
	
	@has one of=Foo; on=fooId

Instead, relations should be specified like so::

	@has.one.of Foo
	@has.one.on fooId

And for old-style composite keys, this::

	@has one of=Foo; on[leftFooId]=rightFooId; on[leftBarId]=rightBarId
 
becomes::

	@has.one.of Foo
	@has.one.on.leftFooId rightFooId
	@has.one.on.leftBarId rightBarId


Note mapper type syntax change
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Simple type declarations like the following should still work fine::

	@type VARCHAR(255) NULL
	@type date
	@type blahblah

For type handlers like ``Amiss\Type\Embed``, which requires additional values in the type declaration,
the syntax has changed. The following will no longer work::

	@type embed ClassName

This should be changed to::

	@type.id embed
	@type.class ClassName


If you have a custom type handler that relies on this extra syntax, it will need to be updated as well.


v2.0.x to v3.0
--------------

New features:

- Added encoder field type. This allows automatic PHP serialization or json_encoding of 
  data in the mapper.
- Added support for embedding objects.
- Added simple MongoDB support

Breaking changes:

- One-to-many relations no longer guess "on" fields - this tended to violate the principle of least
  astonishment. "inverse=relationName" must now be specified to establish bi-directional mapping.
- ``Amiss\Mapper\Note`` no longer adds any types by default - to get the default set from previous
  versions, create it like so: ``$mapper = (new Amiss\Mapper\Note())->addTypeSet(new Amiss\Sql\TypeSet);``
- ``Amiss\Manager`` has been renamed ``Amiss\Sql\Manager``
- ``Amiss\Sql\Manager->getByPk`` has been renamed ``getById``
- ``Amiss\Sql\Manager->deleteByPk`` has been renamed ``deleteById``
- ``\Amiss\Sql\Mapper->exportRow`` has been renamed ``fromObject``
- ``\Amiss\Sql\Mapper->buildObject`` has been renamed ``toObject``
- ``Amiss\Mapper\Note`` now only takes an instance of ``Amiss\Cache`` as its first argument, it no longer
  supports a 2-tuple of closures.
- ``Amiss\Loader`` is no longer a generic loader. It cannot be used for other PSR-0 loading.
