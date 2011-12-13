Modifying
=========

Amiss supports very simple create, update and delete operations on objects, as well as update and delete operations on tables.


Inserting
---------

The ``insert`` method has a variable signature::

    insert ( object $model )
    insert ( string $model, array $params )


Object Insertion
~~~~~~~~~~~~~~~~

Inserting by object is easy: just pass it directly to ``Amiss\Manager::insert``.

If you have an autoincrement ID column it is not populated into the corresponding object field by default, but the value of `PDO\:\:lastInsertId <http://www.php.net/manual/en/pdo.lastinsertid.php>`_ is returned so you can assign it yourself: 

.. code-block:: php

    <?php
    $e = new Event;
    $e->slug = 'foo-bar';
    $e->name = 'Foo Bar';
    
    // assign the autoincrement PK by hand
    $e->eventId = $amiss->insert('Event');


Value Insertion
~~~~~~~~~~~~~~~

When the default behaviour of Object Insertion just won't do, you can insert a list of values directly.

This is useful when:

- You have additional fields on the object that contain scalar values not related to a table column; or
- You would like to have finer, explicit control over the fields to be inserted. 

.. code-block:: php

    <?php
    $eventId = $amiss->insert('Event', array(
        'name'=>'Guns and Roses at The Tote',
        'slug'=>'guns-and-roses-tote'
    ));


Updating
--------

Updating can work on a specific object or a whole table.


Objects
~~~~~~~

To update an object, call the ``update`` method of ``Amiss\Manager``. The following signatures are available::

    update ( object $model , string $primaryKeyField )
    update ( object $object , string $positionalWhere [ , string $param1 ... ] )
    update ( object $object , string $namedWhere , array $params )

The first signature requires you to pass the primary key field's name as the second parameter. This causes Amiss to generate a 'where' clause that isolates the update to the specific object passed as the first argument:

.. code-block:: php
    
    <?php
    $a = $amiss->get('Artist', 'artistId=?', 1);
    $a->name = 'foo bar';
    $amiss->update($a, 'artistId');
    // UPDATE artist SET name='foo bar' WHERE artistId=1


The second and third signatures allow you to update objects that don't have a single column primary key. They behave much like the positional and named where statements described in the "Selecting" chapter:

.. code-block:: php
    
    <?php
    $obj = $amiss->get('EventArtist', 'artistId=? AND eventId=?', 1, 1);
    $obj->priority = 123999;
    $amiss->update($obj, 'artistId=? AND eventId=?', 1, 1);
    // UPDATE artist SET name='foo bar' WHERE artistId=1


Tables
~~~~~~

To update a table, call the ``update`` method of ``Amiss\Manager`` but pass the object's name as the first parameter instead of an instance. The following signatures are available::

    update( string $class, array $set , string $positionalWhere, [ $param1, ... ] )
    update( string $class, array $set , string $namedWhere, array $params )
    update( string $class, array $criteria )
    update( string $class, Amiss\Criteria\Update $criteria )

The ``class`` parameter should just be the name of a class, otherwise the "Object" updating method described above will kick in.

In the first two signatures, the ``set`` parameter is an array of key=>value pairs containing fields to set. The key should be the object's property name, not the column in the database (though these may be identical). The ``positionalWhere`` or ``namedWhere`` are, like select, just parameterised query clauses.

.. code-block:: php
    
    <?php
    $amiss->update('EventArtist', array('priority'=>1), 'artistId=?', 2);
    // equivalent SQL: UPDATE event_artist SET priority=1 WHERE artistId=2


In the second two signatures, an ``Amiss\Criteria\Update`` (or an array-based representation) can be passed:

.. code-block:: php

    <?php
    // array notation
    $amiss->update('EventArtist', array(
        'set'=>array('priority'=>1), 
        'where'=>'artistId=:id', 
        'params'=>array('id'=>2)
    ));
    
    // long-form criteria
    $criteria = new Amiss\Criteria\Update;
    $criteria->set['priority'] = 1;
    $criteria->where = 'artistId=:id';
    $criteria->params = array('id'=>2);
    $amiss->update('EventArtist', $criteria);
    
    // short-form 'where' criteria
    $criteria = new Amiss\Criteria\Update;
    $criteria->set = array('priority'=>1);
    $criteria->where = array('artistId'=>':id');
    $amiss->update('EventArtist', $criteria);


Saving
------

"Saving" is a shortcut for "insert if it's new, update if it isn't", but it only works for objects with an autoincrement column.

.. code-block:: php
    
    <?php
    $obj = new Artist;
    $obj->name = 'foo baz';
    $amiss->save($obj, 'artistId');
    // INSERT INTO artist (name) VALUES ('foo baz')
    
    $obj = $amiss->get('Artist', 'artistId=?', 1);
    $obj->name = 'foo baz';
    $amiss->save($obj, 'artistId');
    // UPDATE artist SET name='foo baz' WHERE artistId=1


How Objects Are Mapped
----------------------

Amiss does not require that you specify any property/field mappings. It does not introspect your database's schema and it does not check your class for annotations. In order to determine what to use when inserting or updating, it has to guess.

By default, inserting and updating by object will enumerate all publicly accessible properties of the object that **aren't an array, an object or null** and `assume they are a column to be saved`:

.. code-block:: php

    <?php
    class FooBar
    {
        // explicit scalar value will be assumed to be a column
        public $yep1='yep';

        // same as above
        public $yep2=2;

        // false !== null, so this is considered a column value
        public $yep3=false;

        // public properties are null by default, so this is skipped
        public $nope1;

        // let's put an array in here later. it won't be considered.
        public $nope2;

        // let's put an object in here later. it won't be considered.
        public $nope3;

        // explicitly null public property, not considered a column
        public $nope4=null;

        // protected properties are not accessible to a foreach loop over an object, 
        // so it is not considered a column value
        protected $nope3='nope';

        // see protected property
        private $nope4='nope';
    }

    $fb = new FooBar;
    $fb->nope2 = array('a', 'b');
    $fb->nope3 = new stdClass;
    $amiss->insert($fb);

    // will generate the following statement:
    // INSERT INTO foo_bar(yep1, yep2, yep3) VALUES(:yep1, :yep2, :yep3)


The rationale for this is as follows:

* Objects are skipped because they are assumed to belong to relations, and should be saved separately
* Arrays have no 1 to 1 representation in MySQL that isn't platform agnostic, and are also likely to represent 1-to-n relations (as in Event->eventArtists)
* An object with a property representing a relation will possibly have a null value. See :ref:`null-handling` for more info.


Custom Mapping
~~~~~~~~~~~~~~

This default behaviour will work in quite a lot more situations than you might be comfortable admitting while you're privately admonishing me for this crazy design decision, but trust me on this - it will. In the event that it doesn't, that's ok: if your object implements the ``RowExporter`` interface, you can build the row up however you please:

.. code-block:: php

    <?php
    class FooBar implements Amiss\RowExporter
    {
        public $name;
        public $anObject;
        public $setNull;
        
        public function exportRow()
        {
            $values = (array)$this;
            $values['anObject'] = serialize($values['anObject']);
            return $values;
        }
    }
    $fb = new FooBar();
    $fb->anObject = new stdClass;
    $manager->insert($fb);


In the above example, ``exportRow()`` will be called by ``Amiss\Manager`` in order to get the values to use in the ``INSERT`` query, completely bypassing the default row export.

I can hear you screaming: "Get your damn hands off my model". I agree. But it could be worse for a domain-model purist: it could be one of those pesky :doc:`/active/index`, rather than an unobtrusive interface.


.. _null-handling:

Null Handling Update Gotcha
~~~~~~~~~~~~~~~~~~~~~~~~~~~

The way Amiss handles nulls is a potentially serious gotcha when performing updates.

Consider the following quick-n-dirty object:

.. code-block:: php

    <?php
    class Pants
    {
        // autoincrement ID
        public $pantsId;
        
        // regular ole field
        public $name;
        
        // this field is also nullable in the database
        public $description=null;
        
        // this represents an ID for a related row. it is not required.
        // the database has a foreign key constraint on this column
        public $pantsTypeId=null;
        
        /**
         * this field holds the related PantsType when it has been retrieved
         * @var PantsType
         */
        public $pantsType;
    }


Using Amiss, we would retrieve and populate this object like so:

.. code-block:: php

    <?php
    $pants = $amiss->get('Pants', 'id=1');
    if ($pants->pantsTypeId)
        $amiss->getRelated(array($pants, 'pantsType'), 'PantsType', 'pantsTypeId');


Depending on the value of ``$pants->pantsTypeId``, the call to ``getRelated`` may or may not happen, so ``$pants->pantsType`` could either be an instance of ``PantsType`` or ``null``. 

If ``pantsTypeId`` is null, or we set it to null, and then we try to update this object, what happens? 

.. code-block:: php

    <?php
    $pants = $amiss->get('Pants', 'id=1');
    $pants->pantsTypeId = null;
    $amiss->update($pants, 'id');


How does Amiss distinguish between ``pantsTypeId`` - which we may actually `want` to set to null - and ``pantsType`` - which does not have a field in the database?

The answer: by default, Amiss just skips both of them.

You can avoid being stung by this a few ways:

* Don't allow NULLs for every field in the database. If you save it for when a field actually needs to be set to null, you will minimise the number of times you actually have to care about this
* Set ``Amiss\Manager->dontSkipNulls`` to true and use getters/setters/private fields for all your related objects
* Implement the ``RowExporter`` interface on every object that has null values that need to be saved
* Don't use the object mode of ``Amiss\Manager->update``, use the table mode and specify the 'set' fields yourself


**Very Important**: ``Amiss\Active\Record``, when used in conjunction with the field definitions outlined in the "Table Creation" section of the "Active Records" documentation, does not have this issue: it knows exactly which fields to use because you told it which fields to use!
