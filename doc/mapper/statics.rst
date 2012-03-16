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


Relations
---------

Relations are declared using a simple array notation. Automatic association table mappings are not supported - you will have to use an intermediary object. Bi-directional relations must be declared explicitly. Feel free to submit a patch for any of this, though it would have to be pretty light to get accepted. This isn't `Doctrine <http://www.doctrine-project.org/>`_, remember?

In the following example, ``Artist`` declares a single-object relation and ``ArtistType`` declares a list relation:

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    class Artist extends \Amiss\Active\Record
    {
        public $artistId;
        public $name;
        public $artistTypeId;

        public static $relations = array(
            'artistType'=>array('one'=>'Amiss\Demo\ArtistType', 'on'=>'artistTypeId'),
        );
    }

    class ArtistType extends \Amiss\Active\Record
    {
        public $artistTypeId;
        public $type;

        public static $relations = array(
            'artists'=>array('many'=>'Amiss\Demo\Artist', 'on'=>'artistId'),
        );
    }
    
    $a = Artist::getByPk(1);
    
    // retrieves the one related artistType
    $type = $a->fetchRelated('artistType');
    
    // retrieves all related artists from the type
    $artists = $type->fetchRelated('artists');


In the relation definition in the above example, the value of the ``one`` and ``many`` relation keys included the fully qualified class name. This is not necessary if you set the value of ``objectNamespace`` against the ``Amiss\Manager``:

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    $amiss = new \Amiss\Manager(...);
    $amiss->objectNamespace = 'Amiss\Demo';
    
    class Artist extends \Amiss\Active\Record
    {
        // ...
        public static $relations = array(
            'artistType'=>array('one'=>'ArtistType', 'on'=>'artistTypeId'),
        );
    }


Relations can also be declared using a method, in case you wish to perform additional gymnastics to make them appear how you want. If you don't define a ``getRelations`` method, it will always just return the value of ``YourRecord::$relations``.

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    class Artist extends \Amiss\Active\Record
    {
        // ...
        public static function getRelations() 
        {
            return array(
                'artistType'=>array('one'=>'ArtistType', 'on'=>'artistTypeId'),
            );
        );
    }

.. warning:: ``getRelations`` will only ever be called once per ``Active\Record`` *class* (not *instance*). Don't do anything that would expect multiple calls.


Unlinke fields, relations are not inheritable. If you delcare relations against one of your active records and then inherit from it, you will need to declare the relations again or merge them yourself. This is where ``getRelations`` comes in handy.

.. code-block:: php

    <?php
    class Foo extends \Amiss\Active\Record
    {
        public static $relations = array(
            'artistType'=>array('one'=>'ArtistType', 'on'=>'artistTypeId'),
        );
    }

    class DerivedFoo extends \Amiss\Active\Record
    {
        public static function getRelations()
        {
            return array_merge(
                Foo::getRelations(),
                array(
                    'somethingElse'=>array('one'=>'SomethingElse', 'on'=>'somethingElseId'),
                ),
            );
        }
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


If you don't specify the types, Amiss will make a guess at what you want them to be. If you're using SQLite, you'll get ``STRING NULL`` columns. If you're using MySQL, you'll get ``VARCHAR(255) NULL`` columns. If this is not what you want, fret not! You can change the default, or you can specify the types on a per-column basis.

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
