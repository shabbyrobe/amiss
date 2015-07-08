Active Records
==============

From `P of EAA`_: An object that wraps a row in a database table or view, encapsulates the
database access, and adds domain logic on that data.

.. _`P of EAA`: http://martinfowler.com/eaaCatalog/activeRecord.html

I'm not wild about Active Records, but they can be very effective for rapid development
and people seem to like them, so why not throw them in?


Defining
--------

To define active records, simply extend the ``Amiss\Sql\ActiveRecord`` class. Configure
everything else just like you would when using Amiss as a Data Mapper.

This guide will assume you are using the :doc:`mapper/annotation`. For more information on
alternative mapping options, see :doc:`mapper/mapping`. Active records will work with any
mapping configuration that works with the data mapper.

.. code-block:: php

    <?php
    class Artist extends Amiss\Sql\ActiveRecord
    {
        /** :amiss = {"field": {"primary": true}}; */
        public $artistId;
   
        /** :amiss = {"field": true}; */
        public $name;
   
        /** :amiss = {"field":true}; */
        public $artistTypeId;
   
        /**
         * :amiss = {
         *     "has": {
         *         "type": "one",
         *         "of": "ArtistType",
         *         "on": "artistTypeId"
         *     }
         * };
         */
        public $artistType;
    }


Connecting
----------

As per the :doc:`configuring` section, create an ``Amiss\Sql\Connector`` and an
``Amiss\Mapper`` and pass it to an ``Amiss\Sql\Manager``. Then, assign the manager to
``Amiss\Sql\ActiveRecord::setManager()``.

.. code-block:: php

    <?php
    $conn = new Amiss\Sql\Connector('sqlite::memory:');
    $manager = Amiss\Factory::createManager($conn);
    Amiss\Sql\ActiveRecord::setManager($manager);
    
    // test it out
    $test = Amiss\Sql\ActiveRecord::getManager();
    var_dump($conn === $manager); // outputs true


Multiple connections are possible, but require subclasses. The separate connections are
then assigned to their respective base class:

.. code-block:: php

    <?php
    abstract class Db1Record extends Amiss\Sql\ActiveRecord {}
    abstract class Db2Record extends Amiss\Sql\ActiveRecord {}
    
    class Artist extends Db1Record {}
    class Burger extends Db2Record {}
    
    Db1Record::setManager($amiss1);
    Db2Record::setManager($amiss2);
    
    // will show 'false' to prove that the record types are not sharing a manager
    var_dump(Artist::getManager() === Burger::getManager());


Querying and Modifying
----------------------

All of the main storage/retrieval methods in ``Amiss\Sql\Manager`` are proxied by
``Amiss\Sql\ActiveRecord``, but for signatures that require the class name or object
instance, ``Amiss\Sql\ActiveRecord`` takes care of passing itself.

When an instance is not required, the methods are called statically against your specific
active record.

Consider the following equivalents:

.. code-block:: php

    <?php
    // inserting
    $mapped = new MappedObject;
    $manager->insert($mapped);
    $manager->save($mapped);
    
    $active = new ActiveObject;
    $active->insert();
    $active->save();
    
    // getting by primary key
    $mapped = $manager->getById('MappedObject', 1);
    $active = ActiveObject::getById(1);

    // assigning relations
    $manager->assignRelated($mapped, 'mappedFriend');
    $active->assignRelated('mappedFriend');


``Amiss\Sql\ActiveRecord`` subclasses make the following **static** methods available:


.. code-block:: php

    <?php
    // get a single active record by primary key
    YourRecord::getById ( $primaryKey );

    // get a single active record
    YourRecord::get ( string $positionalWhere, mixed $param1[, mixed $param2...]);
    YourRecord::get ( string $namedWhere, array $params );
    YourRecord::get ( array $criteria );
    YourRecord::get ( Amiss\Sql\Criteria $criteria );

    // get a list of active records
    YourRecord::getList ( as with get );

    // count active records
    YourRecord::count ( string $positionalWhere, mixed $param1[, mixed $param2...]);
    YourRecord::count ( string $namedWhere, array $params );
    YourRecord::count ( array $criteria );
    YourRecord::count ( Amiss\Sql\Criteria $criteria );


``Amiss\Sql\ActiveRecord`` subclasses make the following **instance** methods available:

.. code-block:: php

    <?php
    $yourRecordInstance->insert ();
    $yourRecordInstance->update ();
    $yourRecordInstance->delete ();
    $yourRecordInstance->save ();


Relations
---------

Relations can be retrieved directly from the active record. This does not change the
parent object:

.. code-block:: php

    <?php
    $record = YourRecord::getById(1);
    $related = $record->getRelated('child');
    assert($record->child == null);


Relations can be populated directly (nothing is returned):

.. code-block:: php

    <?php
    $record = YourRecord::getById(1);
    $record->assignRelated('child');
    assert($record->child != null);


For convenience, ``assignRelated`` also works statically and directly proxies
``Amiss\Sql\Manager->assignRelated()``. No validation is performed on the first argument -
you can pass in anything accepted by the record's associated manager. This method is
simply a bit of syntactic sugar:

.. code-block:: php
    
    <?php
    $data = YourRecord::getList();
    YourRecord::assignRelated($data, 'relation');


Lazy Loading
------------

``Amiss\Sql\ActiveRecord`` has no support for automatic lazy loading. You can implement it
yourself using a wrapper function:

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    
    class Artist extends \Amiss\Sql\ActiveRecord
    {
        public $artistId;
        public $name;
        public $artistTypeId;
        
        private $artistType;
        
        /**
         * :amiss = {
         *     "has": {
         *         "type": "one",
         *         "of": "ArtistType",
         *         "on": "artistTypeId"
         *     }
         * };
         */
        public function getArtistType()
        {
            if ($this->artistType===null && $this->artistTypeId) {
                $this->artistType = $this->getRelated('artistType');
            }
            return $this->artistType;
        }
    }
    

You can then simply call the new function to get the related object:

.. code-block:: php

    <?php
    $a = Artist::getById(1);
    $type = $a->getArtistType();


Hooks
-----

You can define additional behaviour against your Active Record which will occur when certain events
happen inside Amiss.

The ``Amiss\Sql\ActiveRecord`` class defines the following hooks in addition to the ones defined by
``Amiss\Sql\Manager``. I sincerely hope these are largely self explanatory:

* ``beforeInsert()``
* ``beforeUpdate()``
* ``beforeSave()``
* ``beforeDelete()``
    
.. note:: 

    ``beforeSave()`` is called when an item is inserted *or* updated. It is called in
    addition to ``beforeInsert()`` and ``beforeUpdate()``.

ALWAYS call the parent method of the hook when overriding:

.. code-block:: php

    <?php
    class MyRecord extends \Amiss\Sql\ActiveRecord
    {
        // snipped fields, etc
   
        function beforeUpdate()
        {
            parent::beforeUpdate();
            // do your own stuff here
        }
    }

