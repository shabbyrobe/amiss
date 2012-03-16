Static Mapper
=============

.. note:: It is assumed by this mapper that an object and a table are corresponding entities. More complex 
    mapping should be handled using a custom mapper.


If you're a Yii ActiveRecord refugee (or, bog help you, PRADO), this might help ease your migration path. It's a desecration of the sanctity of your model objects, but you know the law... "you gotta do what you gotta do".

When using the static mapper, the absolute bare minimum you have to do to map an object is declare which properties represent its fields:

.. code-block:: php
    
    <?php
    class Artist
    {
        public static $fields = array('artistId', 'name');

        public $artistId;
        public $name;
    }


When inheriting, the Statics mapper will merge the fields with the fields declared against the parent class:

.. code-block:: php

    <?php
    class PantsArtist extends Artist
    {
        public static $fields = array('pants');
    }

    // PantsArtist will map to the following fields:
    // artistId, name, pants

See :ref:`mapper-common` for more information on how to tweak the static mapper's behaviour.


Table Mapping
-------------

By default, the table name will be derived from the object. If you want the object to explicitly declare the table to which it refers, specify a static field called ``table``:

.. code-block:: php
    
    <?php
    class Artist
    {
        public static $table = 'whoopee_artist_yeehaw';
        public static $fields = array('artistId', 'name');

        public $artistId;
        public $name;
    }


Primary Key
-----------

By default, a field with the same name as the object name (namespace excluded) with the "Id" suffix will be assumed to be the primary key. In the case of the following example, the object is called ``Foo``, so it uses the ``fooId`` field as the primary key:

.. code-block:: php

    <?php
    class Foo
    {
        public static $fields = array('fooId', 'name');

        public $fooId;
        public $name;
    }


.. warning:: The static mapper does not support multi-column primary keys. Actually, I'm pretty sure ``Amiss\Manager`` doesn't yet either.


If you wish to change the field it uses for the primary key, simply add a static field called ``primary``:

.. code-block:: php
    
    <?php
    class Artist
    {
        public static $primary = 'thisIsThePrimary';
        public static $fields = array('thisIsThePrimary', 'name');
        public $thisIsThePrimary;
        public $name;
    }


Relations
---------

Relations are declared using a simple array notation. In the following example, ``Artist`` declares a single-object relation and ``ArtistType`` declares a list relation:

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    class Artist
    {
        public $artistId;
        public $name;
        public $artistTypeId;

        public static $fields = array('artistId', 'name', 'artistTypeId');

        public static $relations = array(
            'artistType'=>array('one'=>'Amiss\Demo\ArtistType', 'on'=>'artistTypeId'),
        );
    }

    class ArtistType
    {
        public $artistTypeId;
        public $type;

        public static $relations = array(
            'artists'=>array('many'=>'Amiss\Demo\Artist', 'on'=>'artistId'),
        );
    }
    
    $a = $manager->getByPk('Artist', 1);
    
    // retrieves the one related artistType
    $type = $manager->getRelated($a, 'artistType');
    
    // retrieves all related artists from the type
    $artists = $type->getRelated($type, 'artists');


In the relation definition in the above example, the value of the ``one`` and ``many`` relation keys included the fully qualified class name. This is not necessary if you set the value of ``objectNamespace`` against the mapper:

.. code-block:: php

    <?php
    namespace Amiss\Demo;

    $mapper = new \Amiss\Mapper\Statics;
    $mapper->objectNamespace = 'Amiss\Demo';
    $manager = new \Amiss\Manager($db, $mapper);
    
    class Artist
    {
        // ...
        public static $relations = array(
            'artistType'=>array('one'=>'ArtistType', 'on'=>'artistTypeId'),
        );
    }


Relations can also be declared using a method, in case you wish to perform additional gymnastics to make them appear how you want:

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    class Artist
    {
        // ...
        public static function getRelations() 
        {
            return array(
                'artistType'=>array('one'=>'ArtistType', 'on'=>'artistTypeId'),
            );
        );
    }


Unlinke fields, relations are not inheritable. If you delcare relations against one of your models and then inherit from it, you will need to declare the relations again or merge them yourself. This is where ``getRelations`` comes in handy.

.. code-block:: php

    <?php
    class Foo
    {
        public static $relations = array(
            'artistType'=>array('one'=>'ArtistType', 'on'=>'artistTypeId'),
        );
    }

    class DerivedFoo extends Foo
    {
        public static function getRelations()
        {
            return array_merge(
                parent::getRelations(),
                array(
                    'somethingElse'=>array('one'=>'SomethingElse', 'on'=>'somethingElseId'),
                ),
            );
        }
    }


Type Mapping
------------

Each item in ``$fields`` can optionally specify a field type:

.. code-block:: php
    
    <?php
    class Foo
    {
        public static $fields = array(
            // you don't have to pass the name as the key if there is no type:
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


If you don't specify the types, Amiss will make a guess at what you want them to be if it needs to (for example with the ``Amiss\TableBuilder``. If you're using SQLite, you'll get ``STRING NULL`` columns. If you're using MySQL, you'll get ``VARCHAR(255) NULL`` columns. If this is not what you want, fret not! You can change the default, or you can specify the types on a per-column basis.

By default, the primary key will be created as an autoincrement integer and if ``$primary`` is not set, the name will be inferred from the name of the class. You can override the type of the primary key's column.

You can specify a default field type using the ``$defaultFieldType`` static property:
     
.. code-block:: php
    
    <?php
    class Foo
    {
        public $defaultFieldType = 'foobar';

        public static $fields = array(
            // this will assume the defaultFieldType:
            'bar',

            // this will also assume the defaultFieldType
            'baz'=>true,

            // this will not
            'qux'=>'datetime'
        );
    }

