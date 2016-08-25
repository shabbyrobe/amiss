Annotation Mapper
=================

``Amiss\Sql\Factory::createManager()`` uses ``Amiss\Mapper\Note`` with certain
:doc:`types` preconfigured by default. This should be used as a default starting
point.  You can access the mapper for further configuration after you create the
manager like so:

.. code-block:: php

    <?php
    $config = ['appTimeZone'=>'UTC', 'cache'=>$cache];
    $manager = \Amiss\Sql\Factory::createManager($db, $config);
    $mapper = $manager->mapper;

This will create an ``Amiss\Sql\Manager`` instance with an ``Amiss\Mapper\Note``
instance already assigned. The mapper will have the following :doc:`types`
pre-configured:

- autoinc
- bool
- decimal - Big number handling provided by http://github.com/litipk/php-bignumbers

If you set the ``dbTimeZone`` and ``appTimeZone`` keys in the config array, you
will also get :doc:`type handlers <types>` for dates:

- datetime
- date
- unixtime

See :doc:`common` and :doc:`types` for more information on how to tweak Amiss'
default mapping behaviour.


Annotations
-----------

*Amiss* uses the `Nope library <http://github.com/shabbyrobe/nope>`_ for
annotation support. Annotations in *Nope* have a very simple syntax. They follow
this format and MUST be embedded in doc block comments (``/** */``, not ``/*
*/``)::

    /**
     * :namespace = JSON;
     */

Parsing of an annotation starts as soon as a ``:namespace`` token is seen at the
**start of a line** (the docblock margin and indentation are not counted)::

    /**
     * :parsed = {"json": "object"};
     * This won't be parsed: :notparsed = {"json": "object"};
     */

The JSON object starts immediately after the ``=`` sign and continues until the
first semicolon found which is the last character on a line (excluding
horizontal whitespace)::

    /**
     * :newlines_galore = {
     *     "foo": "bar",
     *     "baz": "qux"
     * };
     *
     * :good_though_ugly = 
     *    {"json": "object"}
     * ;
     *
     * :will_fail = {"json": "object"}
     * ; not the last thing on the line
     */


Overview
--------

All Amiss annotations use the ``:amiss`` namespace.

Using the Annotation mapper, object/table mappings are defined in this way:

.. code-block:: php

    <?php
    /**
     * :amiss = {
     *     "table": "your_table",
     *     "fieldType": "VARCHAR(255)"
     * };
     */
    class Foo
    {
        /** :amiss = {"field": {"primary": true}}; */
        public $id;
   
        /** :amiss = {"field": "some_column"}; */
        public $name;
   
        /** :amiss = {"field": true}; */
        public $barId;
   
        /**
         * One-to-one relation: 
         *
         * :amiss = {"has": {
         *     "type": "one", "of": "Baz", "on": "bazId"
         * }};
         */
        public $baz;
   
        /** 
         * One-to-many relation:
         *
         * :amiss = {
         *     "has": {
         *         "type": "many",
         *         "of": "Bar",
         *         "inverse": "foo"
         *     }
         * };
         */
        public $bars;
   
        // field is defined below using getter/setter
        private $fooDate;
   
        /**
         * :amiss = {"field": {"type": "date"}};
         */
        public function getFooDate()   { return $this->fooDate; }
        public function setFooDate($v) { $this->fooDate = $v; }
    }

It is assumed by this mapper that an object and a table are corresponding
entities. More complex mapping should be handled using a :doc:`custom mapper
<custom>`.


Class Mapping
-------------

These values must be assigned in the class' docblock:

.. code-block:: php

    <?php
    /**
     * :amiss = {
     *     "table": "my_table",
     *     "fieldType": "string-a-doodle-doo"
     * };
     */
    class Foo
    {}


The following class level annotations are available:

``table``:

    When declared, this forces the mapper to use this table name. It may include
    a schema name as well. If not provided, the table name will be determined by
    the mapper. See :ref:`name-translation` for details on this process.


``fieldType``:

    This sets a default field type to use for for all of the properties that do
    not have a field type set against them explicitly. This will inherit from a
    parent class if one is set. See :doc:`types` for more details.


``constructor``:

    The name of a static constructor to use when creating the object instead of
    the default ``__construct``. The method must be static and must return an
    instance of the class.

    If no constructor arguments are found in the metadata (see
    ``constructorArgs``), the entire unmapped input record is passed as the
    first argument.

    .. code-block:: php

        <?php
        /**
         * :amiss = {"constructor": "pants"};
         */
        class Foo
        {
            static function pants(array $input)
            {
                $f = new static();
                $f->value = $input['value'];
                return $f;
            }
        }


Property mapping
----------------

Mapping a property to a column is done inside a property or getter method's
docblock using a JSON object with the key ``field``.

If the value for ``field`` is ``true``, no special additional metadata is
required and the column name is determined by the base mapper. See
:ref:`name-translation` for more details on this process::

    /** :amiss = {"field": true}; */
    public $theField;
    
If the value for ``field`` is a string, it is used as the column name::

    /** :amiss = {"field": "my_column"}; */
    public $theField;

More complex mapping is possible by assigning an object to ``field`` with any of
the following keys:

``name``

    This marks whether a property or a getter method represents a value that
    should be stored in a column.

    This value is optional. If it isn't specified, the column name is determined
    by the base mapper. See :ref:`name-translation` for more details on this
    process.

``type``

    Optional type for the field. If this is not specified, the ``fieldType`` class
    level attribute is used. See :doc:`types` for more details.

    The value for ``type`` can be a string representing a type handler::
        
        /** :amiss = {"field": {"type": "decimal"}}; */
        public $theField;
    
    For type handlers that take additional configuration, you can pass an object
    containing the type handler name assigned to the ``id`` key::

        /** :amiss = {"field": {"type": {"id": "decimal", "scale": 3}}}; */
        public $theField;

``index``

    If this is true, an single-field index with the same name as the property is
    created::
        
        class Pants {
            /** :amiss = {"field": {"index": true}}; */
            public $myIndexedField;
        }
        $meta = $mapper->getMeta(Pants::class);
        assert($meta->indexes['myIndexedField']['fields'] == ['myIndexedField']);

``setter``

    If the ``field`` attribute is set against a getter method as opposed to a
    property, and the getter/setter pair does not follow one of the common
    formats listed below, you can explicitly define the setter using this key::

        /** :amiss = {"field": {"setter": "assignTheFoo"}}; */
        public function gimmeTheFoo()    { ... }
        public function assignTheFoo($v) { ... }

    See :ref:`annotations-getters-setters` for more details.


Relation mapping
----------------

Mapping a property to a column is done inside a property or getter method's
docblock using a JSON object with the key ``has``.

If the value for ``has`` is a string, it is used as the relator name::

    /** :amiss = {"has": "theRelator"}; */
    public $theRelation;

More complex mapping is possible by assigning an object to ``has`` with the key
``type``.  This is equivalent to the previous example::

    /** :amiss = {"has": {"type": "theRelator"}}; */
    public $theRelation;

``type`` must be a short string registered with ``Amiss\Sql\Manager->relators``.
The ``one``, ``many`` and ``assoc`` relators are available by default, which all
require additional configuration using an object.

**One-to-one** (``one``) relationships require, at a minimum, the target object
of the relation and the indexes on which the relation is established. You should
read the :ref:`relator-one` documentation for a full description of the data
this relator requires.  A simple one-to-one is annotated like so:

.. code-block:: php
        
    <?php
    class Artist
    {
        /** :amiss = {"field": {"primary": true}}; */
        public $artistId;
   
        /** :amiss = {"field":true}; */
        public $artistTypeId;
            
        /**
         * :amiss = {"has": {
         *     "type": "one",
         *     "of": "ArtistType",
         *     "on": "artistTypeId"
         * }};
         */
        public $artist;
    }
    

A one-to-one relationship where the left and right side have different field names::

    @has.one.of ArtistType
    @has.one.on.typeId artistTypeId


A one-to-one relationship on a composite key::

    @has.one.of ArtistType
    @has.one.on typeIdPart1
    @has.one.on typeIdPart2


A one-to-one relationship on a composite key with different field names::

    @has.one.of ArtistType
    @has.one.on.typeIdPart1 idPart1
    @has.one.on.typeIdPart2 idPart2
        
    
A one-to-one relationship with a matching one-to-many on the related object,
where the ``on`` values are to be determined from the related object::
        
    @has.one.of ArtistType
    @has.one.inverse artist

    
**One-to-many** (``many``) relationships support all the same options as
one-to-one relationships. You should read the :ref:`relator-many` documentation
for a full description of the data this relator requires. The simplest
one-to-many is annotated like so:

.. code-block:: php

    <?php
    class ArtistType
    {
        /** :amiss = {"field": {"primary": true}}; */
        public $artistTypeId;
   
        /**
         * :amiss = {"has": {
         *     "type": "many",
         *     "of": "Artist",
         *     "on": "artistTypeId"
         * }};
         */
        public $artists;
    }


**Association** (``assoc``) relationships are annotated quite differently. You
should read the :ref:`relator-assoc` documentation for a full description of the
data this relator requires.  A quick example:

.. code-block:: php

    <?php
    class Event
    {
        /** :amiss = {"field": {"primary": true}}; */
        public $eventId;
   
        /**
         * :amiss = {"has": {
         *     "type": "many",
         *     "of": "EventArtist",
         *     "on": "eventId"
         * }};
         */
        public $eventArtists;
   
        /**
         * :amiss = {"has": {
         *     "type": "assoc",
         *     "of": "Artist",
         *     "via": "EventArtist"
         * }};
         */
        public $artists;
    }


``setter``

    If the ``has`` annotation is set against a getter method as opposed to a
    property, this defines the method that is used to set the value when loading
    an object from the database. It is required if the ``has`` attribute is
    defined against a property and the getter/setter method names deviate from
    the standard ``getFoo``/``setFoo`` pattern.

    See :ref:`annotations-getters-setters` for more details.


.. _annotations-getters-setters:

Getters and setters
-------------------

Getters and setters can be used for both fields and relations. When using the
annotation mapper, this should be done against the getter in exactly the same
way as you would do it against a property:

.. code-block:: php

    <?php
    class Foo
    {
        private $baz;
        private $qux;
   
        /** :amiss = {"field":true}; */
        public function getBaz() { return $this->baz; }
   
        /**
         * :amiss = {
         *     "has": {"type": "one", "of": "Qux", "on": "baz"}
         * };
         */
        public function getQux() { return $this->qux; }
    }

There is a problem with the above example: we have provided a way to get the
values, but not to set them. This will make it impossible to retrieve the object
from the database. If you provide matching ``setBaz`` and ``setQux`` methods,
Amiss will guess that these are paired with ``getBaz`` and ``getQux``
respectively and don't require any special annotations:

.. code-block:: php

    <?php
    class Foo
    {
        public function setBaz($value)
        {
            $value->thingy = $this;
            $this->baz = $value;
        }
   
        public function setQux($value)
        {
            $value->thingy = $this;
            $this->qux = $value;
        }
    }


If your getter/setter pair doesn't follow the ``getFoo/setFoo`` standard, you
can specify the setter directly against both relations and fields using the
``setter`` property of the ``field`` annotation.  The following example should
give you some idea of my opinion on going outside the standard, but Amiss tries
not to be too opinionated so you can go ahead and make your names whatever you
please:

.. code-block:: php

    <?php
    class Foo
    {
        private $baz;
        private $qux;
   
        /**
         * :amiss = {
         *     "field": {"setter": "assignAValueToBaz"}
         * };
         */
        public function getBaz() { return $this->baz; }
   
        public function assignAValueToBaz($value)
        {
            $value->thingy = $this;
            $this->baz = $value;
        }
   
        /**
         * :amiss = {
         *     "has": {"type": "one", "of": "Qux", "on": "baz"},
         *     "field": {"setter": "makeQuxEqualTo"}
         * };
         */
        public function pleaseGrabThatQuxForMe() 
        {
            return $this->qux;
        }
   
        public function makeQuxEqualTo($value)
        {
            $value->thingy = $this;
            $this->qux = $value;
        }
    }


Caching
-------

``Amiss\Mapper\Note`` provides a facility to cache reflected metadata. This is
not strictly necessary: the mapping process only does a little bit of reflection
and is really very fast, but you can get up to 30% more speed out of Amiss in
circumstances where you're doing even just a few metadata lookups per request
(say, running one or two queries against one or two objects) by using a cache.

The simplest way to enable caching is to create an instance of ``Amiss\Cache``
with a callable getter and setter as the first two arguments, then pass it as
the first constructor argument of ``Amiss\Maper\Note``. Many of the standard PHP
caching libraries can be used in this way:

.. code-block:: php

    <?php
    $cache = new \Amiss\Cache('apc_fetch', 'apc_store');
    $cache = new \Amiss\Cache('xcache_get', 'xcache_set');
    $cache = new \Amiss\Cache('eaccelerator_get', 'eaccelerator_put');
    
    // when using the SQL manager's default note mapper:
    $manager = \Amiss\Sql\Factory::createManager($db, array('cache'=>$cache));
    
    // when creating the mapper by hand
    $mapper = new \Amiss\Mapper\Note($cache);
    $manager = \Amiss\Sql\Factory::createManager($db, $mapper);


By default, no TTL or expiration information will be passed by the mapper. In
the case of ``apc_store``, for example, this will mean that once cached, the
metadata will never invalidate.  If you would like an expiration to be passed,
you can either pass it as the fourth argument to the cache's constructor (the
third argument is explained later), or set it against the ``expiration``
property:

.. code-block:: php

    <?php
    // Using the constructor
    $cache = new \Amiss\Cache('apc_fetch', 'apc_store', null, 86400);
   
    // Or setting by hand
    $cache = new \Amiss\Cache('apc_fetch', 'apc_store');
    $cache->expiration = 86400;


You can set a prefix for the cache in case you want to ensure Amiss does not
clobber items that other areas of your application may be caching:

.. code-block:: php

    <?php
    $cache = new Amiss\Cache('xcache_get', 'xcache_set');
    $cache->prefix = 'dont-tread-on-me-';
    

You can also use closures:

.. code-block:: php

    <?php
    $cache = new \Amiss\Cache(
        function ($key) {
            // get the value from the cache
        },
        function ($key, $value, $expiration) {
            // set the value in the cache
        }
    );


If you would rather use your own caching class, you can pass it directly to
``Amiss\Mapper\Note`` if it has following method signatures:

.. code-block:: php

    <?php
    class MyCache
    { 
        public function get($key) {}
        public function set($key, $value, $expiration=null) {}
    }
    $cache = new MyCache;
    $mapper = new Amiss\Mapper\Note($cache);


The ``$expiration`` parameter to ``set()`` is optional. It will be passed, but
you can ignore it and PHP doesn't require that it be present in your method
signature.

If your class does not support this interface, you can use ``Amiss\Cache`` to
wrap your own class by passing the names of the getter and setter methods and
your own class:

.. code-block:: php

    <?php
    class MyCache
    { 
        public function fetch($key) {}
        public function put($key, $value) {}
    }
    $cache = new MyCache;
    $cacheAdapter = new Amiss\Cache('fetch', 'put', $cache);
    $mapper = new Amiss\Mapper\Note($cacheAdapter);


.. warning:: 

    Don't use a cache in your development environment otherwise you'll have to
    clear the cache every time you change your models!

    Set an environment variable (see `SetEnv
    <https://httpd.apache.org/docs/2.2/mod/mod_env.html#setenv>`_  for apache or
    ``export`` for bash), then do something like this:

    .. code-block:: php
        
        <?php
        // give it a better name than this!
        $env = getenv('your_app_environment');
        
        $cache = null;
        if ($env != 'dev') {
            $cache = new \Amiss\Cache('apc_fetch', 'apc_store');
        }
        
        $mapper = new \Amiss\Mapper\Note($cache);

