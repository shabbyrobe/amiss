Changelog
=========

v4.1.0 to v4.2.0
----------------

- Active Records support ``deleteById`` as a static method.

- ``Mapper->toObject``, ``Mapper->fromObject``, ``Mapper->createObject`` etc
  support accepting a string as well as an instance of ``Amiss\Meta``.

- ``Amiss\Type\Date`` now allows you to specify a subclass of ``DateTime`` to
  use instead of ``DateTime``.

- Moved to packagist


v4.0.x to v4.1.0
----------------

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


v3.0.x to v4.0
--------------

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

	$manager = Amiss::createSqlManager($conn);


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
