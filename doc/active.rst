Active Records
==============

From `P of EAA`_:
An object that wraps a row in a database table or view, encapsulates the database access, and adds domain logic on that data.

.. _`P of EAA`: http://martinfowler.com/eaaCatalog/activeRecord.html

I'm not in love with this pattern, but I have used it in the past with some other libraries. This has been added to facilitate a migration for an old project of mine, but people seem to be quite fond of Active Records so why not include it.

``Amiss\Active\Record`` is an Active Record wrapper around ``Amiss\Manager``. It's not fancy, it's not good, it's not fully-featured, but it does seem to work OK for the quick-n-dirty ports I've done.

It does place the following constraints:

* All Active Records must have an autoincrement primary key if you want to use the ``save`` method. If not, you'll still be able to use ``insert`` and ``update``.
* Class Hierarchies that use a separate connection must declare a base class.


Connecting
----------

As per the :doc:`connecting` section, create an ``Amiss\Manager``, then pass it to ``Amiss\Active\Record::setManager()``.

.. code-block:: php

    <?php
    $conn = new Amiss\Connector('sqlite::memory:');
    $amiss = new Amiss\Manager($conn);
    Amiss\Active\Record::setManager($amiss);
    
    // test it out
    $test = Amiss\Active\Record::getConnector();
    var_dump($conn === $test); // outputs true


Multiple connections are possible, but require subclasses. The separate connections are then assigned to their respective base class:

.. code-block:: php

    <?php
    abstract class Db1Record extends Amiss\Active\Record {}
    abstract class Db2Record extends Amiss\Active\Record {}
    
    class Artist extends Db1Record {}
    class Burger extends Db2Record {}
    
    Db1Record::setManager($amiss1);
    Db2Record::setManager($amiss2);
    
    // will show 'false' to prove that the record types are not 
    // sharing a connection class
    var_dump(Artist::getManager() === Burger::getManager());


Relations
=========

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




Lazy Loading
~~~~~~~~~~~~

``Amiss\Active\Record`` has no support for automatic lazy loading. You can implement it yourself using a wrapper function:

.. code-block:: php
    
    <?php
    namespace Amiss\Demo;
    class Artist extends \Amiss\Active\Record
    {
        public $artistId;
        public $name;
        public $artistTypeId;
        
        private $artistType;

        public static $relations = array(
            'artistType'=>array('one'=>'ArtistType', 'on'=>'artistTypeId'),
        );
        
        public function getArtistType()
        {
            if ($this->artistType===null && $this->artistTypeId) {
                $this->artistType = $this->fetchRelated('artistType');
            }
            return $this->artistType;
        }
    }
    

You can then simply call the new function to get the related object:

.. code-block:: php
    
    <?php
    $a = Artist::getByPk(1);
    $type = $a->getArtistType();
    
