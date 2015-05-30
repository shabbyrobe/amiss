Modifying
=========

Objects
-------

``\Amiss\Sql\Manager->insert(...)``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``insert`` method inserts mapped objects into the database and supports the following
signatures::

    $manager->insert( $object )
    $manager->insert( $object , string $table )

Inserting by object is simple: just pass it directly to ``Amiss\Sql\Manager::insert``.

.. code-block:: php
    
    <?php
    $e = new Event;
    $e->setName('Foo Bar');
    $manager->insert($e);

    // autoinc fields are populated automatically
    echo $e->eventId;


Multiple insertions of the same object are not prevented by Amiss. An appropriately
configured primary or unique key will allow your database to prevent undesired duplicates.

You can manually specify which table to insert into, overriding the table stored in the
``Amiss\Meta`` for the class:

.. code-block:: php
    
    <?php
    $e = new Event;
    $manager->insert($e, 'some_other_table');


``Amiss\Sql\Manager->update(...)``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``update`` method updates mapped objects into the database and supports the following
signatures::

    $manager->update( $object )
    $manager->update( $object , string $table )

Updating an object requires a primary key be defined in the :doc:`metadata`.

.. code-block:: php

    <?php
    $a = $manager->getById('Artist', 1);
    $a->name = 'foo bar';
    $manager->update($a);
    // UPDATE artist SET name='foo bar' WHERE artistId=1

You can manually specify which table to update, overriding the table stored in the
``Amiss\Meta`` for the class.


``Amiss\Sql\Manager->delete(...)``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``delete`` method removes a mapped object from the database and supports the following
signatures::

    $manager->delete( $object )
    $manager->delete( $object , string $table )

Deleting an object requires a primary key be defined in the :doc:`metadata`.

.. code-block:: php

    <?php
    $a = $manager->getById('Artist', 1);
    $a->name = 'foo bar';
    $manager->delete($a);
    unset($a);

The instance of the object is not modified by the delete operation - it is up to you to
get rid of the instance if you're done with it. You are free to re-insert it if you like.

You can manually specify which table to update, overriding the table stored in the
``Amiss\Meta`` for the class.


Value Insertion
~~~~~~~~~~~~~~~

When the default behaviour of Object Insertion just won't do, you can insert a list of values
directly.

This is useful when

- You want to do a quick and dirty insert of just a few values; or
- You would like to have finer, explicit control over the fields to be inserted. 

.. code-block:: php

    <?php
    $eventId = $amiss->insert('Event', array(
        'name'=>'Guns and Roses at The Tote',
        'slug'=>'guns-and-roses-tote'
    ));

.. note:: This is kind of a throwback to an earlier version. It may be removed at some point.


Updating
--------

Updating can work on a specific object or a whole table.


Objects
~~~~~~~

To update an object's representation in the database, call the ``update`` method of
``Amiss\Sql\Manager`` with the object as the argument.

.. note:: This only works if the object has a primary key.

.. code-block:: php

    <?php
    $a = $manager->getById('Artist', 1);
    $a->name = 'foo bar';
    $manager->update($a);
    // UPDATE artist SET name='foo bar' WHERE artistId=1


Tables
~~~~~~

To update a table, call the ``update`` method of ``Amiss\Sql\Manager`` but pass the object's name as
the first parameter instead of an instance. The following signatures are available::

    update( string $class, array $set , string $positionalWhere, [ $param1, ... ] )
    update( string $class, array $set , string $namedWhere, array $params )
    update( string $class, array $criteria )
    update( string $class, Amiss\Sql\Criteria\Update $criteria )


The ``class`` parameter should just be the name of a class, otherwise the "Object" updating method
described above will kick in.

In the first two signatures, the ``set`` parameter is an array of key=>value pairs containing fields
to set. The key should be the object's property name, not the column in the database (though these
may be identical). The ``positionalWhere`` or ``namedWhere`` are, like select, just parameterised
query clauses. See :ref:`clauses` for more information.

.. code-block:: php

    <?php
    $manager->update('EventArtist', array('priority'=>1), '{artistId}=?', 2);
    // equivalent SQL: UPDATE event_artist SET priority=1 WHERE artistId=2


In the second two signatures, an ``Amiss\Sql\Criteria\Update`` (or an array-based representation)
can be passed:

.. code-block:: php

    <?php
    // array notation
    $manager->update('EventArtist', array(
        'set'=>array('priority'=>1), 
        'where'=>'{artistId}=:id', 
        'params'=>array('id'=>2)
    ));
    
    // long-form criteria
    $criteria = new Amiss\Sql\Criteria\Update;
    $criteria->set['priority'] = 1;
    $criteria->where = '{artistId}=:id';
    $criteria->params = array('id'=>2);
    $manager->update('EventArtist', $criteria);
    
    // short-form 'where' criteria
    $criteria = new Amiss\Sql\Criteria\Update;
    $criteria->set = array('priority'=>1);
    $criteria->where = array('artistId'=>':id');
    $manager->update('EventArtist', $criteria);


Saving
------

"Saving" is a shortcut for "insert if it's new, update if it isn't", but it only works for objects 
with an autoincrement column.

.. code-block:: php

    <?php
    $obj = new Artist;
    $obj->name = 'foo baz';
    $amiss->save($obj, 'artistId');
    // INSERT INTO artist (name) VALUES ('foo baz')
    
    $obj = $amiss->get('Artist', '{artistId}=?', array(1));
    $obj->name = 'foo baz';
    $amiss->save($obj, 'artistId');
    // UPDATE artist SET name='foo baz' WHERE artistId=1


Deleting
--------

Deleting by object works the same way as updating by object::

    delete( object $object )


Deleting by table::

    delete( string $table, string $positionalWhere, [ $param1, ... ] )
    delete( string $table, string $namedWhere, array $params )
    delete( string $table, array $criteria )
    delete( string $table, Criteria\Query $criteria )


.. note:: 

    Deleting by table cannot be used with an empty "where" clause. If you really want to delete
    everything in a table, you should either truncate directly:

    .. code-block:: php

        <?php
        $manager->execute("TRUNCATE TABLE ".$manager->getMeta('Object')->table);


    Or pass a "match everything" clause:

    .. code-block:: php
    
        <?php
        $manager->delete('Object', '1=1');


Tables
------

    $manager->insertTable( $meta , array $propertyValues );
    $manager->insertTable( $meta , Query\Insert $query );

