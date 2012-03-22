Active Records
==============

From `P of EAA`_:
An object that wraps a row in a database table or view, encapsulates the database access, and adds domain logic on that data.

.. _`P of EAA`: http://martinfowler.com/eaaCatalog/activeRecord.html

I'm not in love with this pattern, but I have used it in the past with some other libraries. This has been added to facilitate a migration for an old project of mine, but people seem to be quite fond of Active Records so why not include it.

``Amiss\Active\Record`` is an Active Record wrapper around ``Amiss\Manager``. It's not fancy, it's not good, it's not fully-featured, but it does seem to work OK for the quick-n-dirty ports I've done.


Defining
--------

To define active records, simply extend the ``Amiss\Active\Record class``. Configure everything else just like you would when using Amiss as a Data Mapper.

This guide will assume you are using the :doc:`mapper/annotation`. For more information on alternative mapping options, see :doc:`mapping`.

.. code-block: php

    <?php
    class Artist extends Amiss\Active\Record
    {
        /** @primary */
        public $artistId;

        /** @field */
        public $name;

        /** @field */
        public $artistTypeId;

        /** @has one of=ArtistType; on=artistTypeId */
    }


Connecting
----------

As per the :doc:`configuring` section, create an ``Amiss\Connector`` and an ``Amiss\Mapper`` and pass it to an ``Amiss\Manager``. Then, assign the manager to ``Amiss\Active\Record::setManager()``.

.. code-block:: php

    <?php
    $conn = new Amiss\Connector('sqlite::memory:');
    $mapper = new Amiss\Mapper\Note;
    $manager = new Amiss\Manager($conn, $mapper);
    Amiss\Active\Record::setManager($manager);
    
    // test it out
    $test = Amiss\Active\Record::getManager();
    var_dump($conn === $manager); // outputs true


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
    // sharing a manager
    var_dump(Artist::getManager() === Burger::getManager());


Querying and Modifying
----------------------

All of the main storage/retrieval methods in ``Amiss\Manager`` are proxied by ``Amiss\Active\Record``, but for signatures that require the class name or object instance, ``Amiss\Active\Record`` takes care of passing itself.

When an instance is not required, the methods are called statically against your specific active record.

Consider the following equivalents:

.. code-block:: php

    <?php
    // inserting
    $mapped = new MappedObject;
    $manager->insert($mapped);
    
    $active = new ActiveObject;
    $active->save();
    
    // getting by primary key
    $mapped = $manager->getByPk('MappedObject', 1);
    $active = ActiveObject::getByPk(1);

    // assigning relations
    $manager->assignRelated($mapped, 'mappedFriend');
    $active->assignRelated('mappedFriend');


``Amiss\Active\Record`` subclasses make the following **static** methods available::

    get ( string $positionalWhere, mixed $param1[, mixed $param2...])
    get ( string $namedWhere, array $params )
    get ( array $criteria )
    get ( Amiss\Criteria $criteria )

    getList ( as with get )

    getByPk ( $primaryKey )

    count ( string $positionalWhere, mixed $param1[, mixed $param2...])
    count ( string $namedWhere, array $params )
    count ( array $criteria )
    count ( Amiss\Criteria $criteria )


``Amiss\Active\Record`` subclasses make the following **instance** methods available::

    getRelated ( $source, $relationName )
    assignRelated ( $into, $relationName )


Lazy Loading
------------

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


Hooks
-----

You can define additional behaviour against your Active Record which will occur when certain events happen inside Amiss.

The ``Amiss\Active\Record`` class defines the following hooks in addition to the ones defined by ``Amiss\Manager``. I sincerely hope these are largely self explanatory:

* ``beforeInsert()``
* ``beforeUpdate()``
* ``beforeSave()``
* ``beforeDelete()``
    
.. note:: ``beforeSave()`` is called when an item is inserted *or* updated. It is called in addition to ``beforeInsert()`` and ``beforeUpdate()``.

ALWAYS call the parent method of the hook when overriding:

.. code-block:: php

    <?php
    class MyRecord extends \Amiss\Active\Record
    {
        // snipped fields, etc

        function beforeUpdate()
        {
            parent::beforeUpdate();
            // do your own stuff here
        }
    }

