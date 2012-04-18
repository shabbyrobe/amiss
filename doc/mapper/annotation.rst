Annotation Mapper
=================

To use the annotation mapper with Amiss, pass an instance of ``Amiss\Mapper\Note`` to ``Amiss\Manager``:

.. code-block:: php

    <?php
    $mapper = new \Amiss\Note\Mapper();
    $manager = new \Amiss\Manager($db, $mapper);


This is the bare minimum required to create an instance of this mapper. This will be enough for now, though more configuration options are available. See :ref:`mapper-common` for more information on how to tweak the behaviour of all of Amiss' default mapping options.


Overview
--------

Using the Annotation mapper, object/table mappings are defined in this way:

.. code-block:: php

    <?php
    /**
     * @table your_table
     * @fieldType VARCHAR(255)
     */
    class Foo
    {
        /** @primary */
        public $id;

        /** @field some_column */
        public $name;

        /** @field */
        public $barId;

        /** 
         * One-to-many relation:
         * @has many of=Bar 
         */
        public $bars;

        /**
         * One-to-one relation: 
         * @has one of=Baz; on=bazId
         */
        public $baz;

        // field is defined below using getter/setter
        private $fooDate;

        /**
         * @field
         * @type date
         */
        public function getFooDate()
        {
            return $this->fooDate;
        }

        public function setFooDate($value)
        {
            $this->fooDate = $value;
        }
    }

It is assumed by this mapper that an object and a table are corresponding entities. More complex mapping should be handled using a :doc:`custom mapper <custom>`.


Annotations
-----------

Annotations are javadoc-style key/values and are formatted like so:

.. code-block:: php

    <?php
    /**
     * @key this is the value
     */


The ``Amiss\Note\Parser`` class is used to extract these annotations. Go ahead and use it in your own application if you find it useful, but keep in mind the following:

 * Everything up to the first space is considered the key. Use whatever symbols 
   you like for the key as long as it isn't whitespace.

 * The value starts after the first space after the key and ends at the first newline. 
   Currently, RFC 2822 style folding is not supported (though it may be in future if it 
   is needed by Amiss). The value is *not trimmed for whitespace*.

 * Multiple annotations per line are *not supported*.


Class Mapping
-------------

The following class level annotations are available:

.. py:attribute:: @table value

    When declared, this forces the mapper to use this table name. If not provided, the table name will be determined by the mapper. See :ref:`name-translation` for details on this process.


.. py:attribute:: @fieldType value

    This sets a default field type to use for for all of the properties that do not have a field type set against them explicitly. This will inherit from a parent class if one is set.


These values must be assigned in the class' docblock:

.. code-block:: php

    <?php
    /**
     * @table my_table
     * @fieldType string-a-doodle-doo
     */
    class Foo
    {}


Property mapping
----------------

Mapping a property to a column is done inside a property or getter method's docblock.

The following annotations are available to define this mapping:

.. py:attribute:: @field columnName

    This marks whether a property or a getter method represents a value that should be stored in a column.

    The ``columnName`` value is optional. If it isn't specified, the column name is determined by the base mapper. See :ref:`name-translation` for more details on this process.


.. py:attribute:: @type fieldType

    Optional type for the field. If this is not specified, the ``@fieldType`` class level attribute is used.


.. py:attribute:: @setter setterName

    If the ``@field`` attribute is set against a getter method as opposed to a property, this defines the method that is used to set the value when loading an object from the database. It is required if the ``@field`` attribute is defined against a property that has a getter/setter name pair that doesn't follow the traditional ``getFoo``/``setFoo`` pattern.

    See :ref:`annotations-getters-setters` for more details.


Relation mapping
----------------

Mapping an object relation is done inside a property or getter method's docblock.

The following annotations are available to define this mapping:

.. py:attribute:: @has relationType relationParams

    Defines a relation against a property or getter method.

    ``relationType`` must be a short string registered with ``Amiss\Manager->relators``. The ``one``, ``many`` and ``assoc`` relators are available by default.

    ``relationParams`` allows you to pass an array of key/value pairs to instruct the relator referred to by ``relationType`` how to handle retrieving the related objects.

    ``relationParams`` is basically a query string with a few enhancements. Under the hood, Amiss just uses PHP's stupidly named `parse_str <http://php.net/parse_str>`_ function. You can use anything you would otherwise be able to use in a query string, like:

        * ``url%20encoding%21``
        * ``space+encoding``
        * ``array[parameters]=yep``
        * ``many=values&are=ok``
    
    As well as a few bits of syntactic sugar that gets cleaned up before parsing, like:
        
        * ``semicolon=instead;of=ampersand;for=readability``
        * ``whitespace = around ; separators = too``
    
    You're free to use whatever you feel will be most readable, but my personal preference is for this format, which is used throughout this guide::

        foo=bar; this=that; array[a]=yep
    
    This saves Amiss the trouble of requiring you to learn a complicated annotation syntax to represent complex data, with the added benefit of being mostly implemented in C.

    **One-to-one** (``@has one``) relationships require, at a minimum, the target object of the relation and the field(s) on which the relation is established. You should read the :ref:`relator-one` documentation for a full description of the data this relator requires. A simple one-to-one is annotated like so:

    .. code-block:: php
        
        <?php
        class Artist
        {
            /** @primary */
            public $artistId;

            /** @field */
            public $artistTypeId;
            
            /** @has one of=ArtistType; on=artistTypeId
            public $artist;
        }
    

    A one-to-one relationship where the left and right side have different field names::

        @has one of=ArtistType; on[typeId]=artistTypeId


    A one-to-one relationship on a composite key::

        @has one of=ArtistType; on[]=typeIdPart1; on[]=typeIdPart2


    A one-to-one relationship on a composite key with different field names::

        @has one of=ArtistType; on[typeIdPart1]=idPart1; on[typeIdPart2]=idPart2
    
    
    **One-to-many** (``@has many``) relationships support all the same options as one-to-one relationships, with the added convenience of the ``on`` key being optional. You should read the :ref:`relator-many` documentation for a full description of the data this relator requires. The simplest one-to-many is annotated like so:

    .. code-block:: php

        <?php
        class ArtistType
        {
            /** @primary */
            public $artistTypeId;

            /** @has many of=Artist */
            public $artists;
        }


    **Association** (``@has assoc``) relationships are annotated quite differently. You should read the :ref:`relator-assoc` documentation for a full description of the data this relator requires. A quick example:

    .. code-block:: php

        <?php
        class Event
        {
            /** @primary */
            public $eventId;

            /** @has many of=EventArtist */
            public $eventArtists;

            /** @has assoc of=Artist; via=EventArtist */
            public $artists;
        }
    



.. py:attribute:: @setter setterName

    If the ``@has`` attribute is set against a getter method as opposed to a property, this defines the method that is used to set the value when loading an object from the database. It is required if the ``@has`` attribute is defined against a property and the getter/setter method names deviate from the standard ``getFoo``/``setFoo`` pattern.

    See :ref:`annotations-getters-setters` for more details.


.. _annotations-getters-setters:

Getters and setters
-------------------

Properties should almost always be defined against your object as class-level fields in PHP. Don't use getters and setters when you are doing no more than getting or setting a private field value - it's a total waste of resources. See this `stackoverflow answer <http://stackoverflow.com/a/813099/15004>`_ for a more thorough explanation of why you shouldn't, and for a brief explanation of how to get all of the benefits anyway.

Having said that, getters and setters are essential when you need to do more than just set a private value.

Getters and setters can be used for both fields and relations. When using the annotation mapper, this should be done against the getter in exactly the same way as you would do it against a property:

.. code-block:: php

    <?php
    class Foo
    {
        private $baz;
        private $qux;

        /** @field */
        public function getBaz() {
            return $this->baz;
        }

        /** @has one of=Qux; on=baz */
        public function getQux() {
            return $this->qux;
        }
    }

There is a problem with the above example: we have provided a way to get the values, but not to set them. This will make it impossible to retrieve the object from the database. If you provide matching ``setBaz`` and ``setQux`` methods, Amiss will guess that these are paired with ``getBaz`` and ``getQux`` respectively:

.. code-block:: php

    <?php
    class Foo
    {
        // snip

        public function setBaz($value) {
            $value->thingy = $this;
            $this->baz = $value;
        }

        public function setQux($value) {
            $value->thingy = $this;
            $this->qux = $value;
        }
    }


If your getter/setter pair doesn't follow the ``getFoo/setFoo`` standard, you can specify the setter directly against both relations and fields using the ``@setter`` annotation. The following example should give you some idea of my opinion on going outside the standard, but Amiss tries not to be too opinionated so you can go ahead and make your names whatever you please:

.. code-block:: php

    <?php
    class Foo
    {
        private $baz;
        private $qux;

        /** 
         * @field
         * @setter assignAValueToBaz
         */
        public function getBaz() {
            return $this->baz;
        }

        public function assignAValueToBaz($value) {
            $value->thingy = $this;
            $this->baz = $value;
        }

        /** 
         * @has one of=Qux; on=baz
         * @setter makeQuxEqualTo
         */
        public function pleaseGrabThatQuxForMe() {
            return $this->qux;
        }

        public function makeQuxEqualTo($value) {
            $value->thingy = $this;
            $this->qux = $value;
        }
    }


Caching
-------

``Amiss\Mapper\Note`` provides a facility to cache reflected metadata. This is not strictly necessary: the mapping process only does a little bit of reflection and is really very fast, but you can get up to 30% more speed out of Amiss in circumstances where you're doing a high number of metadata lookups per query (say, running one or two queries against one or two objects) by using a cache.

The simplest way to enable caching is to pass the string ``apc`` as the first argument. This will use ``apc_fetch`` and ``apc_store`` with an expiry of 1 day:

.. code-block:: php

  <?php
  $mapper = new \Amiss\Note\Mapper('apc');


If you don't want to use APC for the cache, or you're not happy with Amiss' default cache lifetime, or you want to allow the mapper to use your own class for caching, you can pass a :term:`2-tuple` of closures. The first member should be your "get" method. It should take a single key argument and return the cached value. The second member should be your "set" method and take key and value arguments.

For example, to shove your cached metadata into the system's temp directory:

.. code-block:: php

    <?php
    $path = sys_get_temp_dir();
    $cache = array(
        function ($key) use ($path) {
            $key = md5($key);
            $file = $path.'/nc-'.$key;
            if (file_exists($file)) {
                return unserialize(file_get_contents($file));
            }
        },
        function ($key, $value) use ($path) {
            $key = md5($key);
            $file = $path.'/nc-'.$key;
            file_put_contents($file, serialize($value));
        }
    );
    $mapper = new \Amiss\Mapper\Note($cache);


.. note:: Don't use a cache in your development environment otherwise you'll have to clear the 
    cache every time you change your models! 

    Set an environment variable (see `SetEnv <https://httpd.apache.org/docs/2.2/mod/mod_env.html#setenv>`_ 
    for apache or ``export`` for bash), then do something like this:

    .. code-block:: php
        
        <?php
        // give it a better name than this!
        $env = getenv('your_app_environment');
        
        $cache = $env == 'dev' ? null : 'apc';
        $mapper = new \Amiss\Note\Mapper('apc');

