Quick Start
===========

.. contents::


This quickstart will assume you wish to use an annotation-based mapper. See :doc:`mapping` for more details and alternatives.


Loading and Configuring
-----------------------

See :doc:`configuring` and :doc:`mapping` for more info.

.. code-block:: php

    <?php

    // Include autoloader. Amiss is PSR-0 compliant, so you can use any loader that supports that standard.
    require_once('/path/to/amiss/src/Loader.php');
    spl_autoload_register(array(new Amiss\Loader, 'load'));

    // Amiss requires a class that implements \Amiss\Mapper in order to get information about how 
    // your objects map to tables
    $mapper = new Amiss\Mapper\Note;

    // This is basically a PDO with a bit of extra niceness. Don't use a PDO though.
    $connector = new Amiss\Connector('mysql:host=127.0.0.1', 'user', 'password');

    // And this binds the whole mess together
    $manager = new Amiss\Manager($connector, $mapper);


Defining objects
----------------

See :doc:`mapping` for more info and advanced topics.

.. code-block:: php

    <?php

    class Event
    {
        /** @primary */
        public $eventId;

        /** @field */
        public $name;

        /** @field */
        public $startDate;

        /** @field */
        public $venueId;

        /** @has one Venue venueId */
        public $venue;
    }

    /**
     * Explicit table name annotation. Leave this out and the table will be assumed
     * to be 'venue'
     * @table venues
     */
    class Venue
    {
        /** @primary */
        public $venueId;

        /** @field venueName */
        public $name;

        /** @field */
        public $slug;

        /** @field */
        public $address;

        /** @has one Event */
        public $events;
    }


Creating Tables
---------------

See :doc:`schema` for more info.

.. code-block:: php

    <?php
    $tableBuilder = new Amiss\TableBuilder($manager, 'Venue');
    $tableBuilder->createTable();


Selecting
---------

See :doc:`selecting` for more info.

.. code-block:: php

    <?php
    // get venue by primary key
    $event = $manager->getByPk('Event', 1);

    // get an event named foobar
    $event = $manager->get('Event', 'name=?', 'foobar');

    // get all events
    $events = $manager->getList('Event');

    // get all venues named foo
    $events = $manager->getList('Event', 'name=?', 'foo');

    // get all events with 'foo' in the name using positional parameters
    $events = $manager->getList(array('where'=>'name LIKE ?', 'params'=>array('%foo%')));

    // get all events with 'foo' in the name using named parameters
    $events = $manager->getList(array('where'=>'name LIKE :foo', 'params'=>array(':foo'=>'%foo%')));

    // paged list, limit/offset
    $events = $manager->getList(array('where'=>'name="foo"', 'limit'=>10, 'offset'=>30));

    // paged list, alternate style (number, size)
    $events = $manager->getList(array('where'=>'name="foo"', 'page'=>array(1, 30)));


Relations
---------

Amiss supports one-to-one and one-to-many relations, and provides a plugin for adding additional relationship retrieval methods. See :doc:`relations` for more info.

One-to-one relations:

.. code-block:: php

    <?php
    // get a one-to-one relation for an event
    $venue = $manager->getRelated($event, 'venue');

    // assign a one-to-one to an event
    $manager->assignRelated($event, 'venue');

    // get each one-to-one relation for all events in a list
    $events = $manager->getList('Event');
    $venueMap = $manager->getRelated($events, 'venue');
    
    // assign each one-to-one relation to all events in a list
    $events = $manager->getList('Event');
    $manager->assignRelated($events, 'venue');


One-to-many relations:

.. code-block:: php

    <?php
    // get a one-to-many relation for a venue. this will return an array
    $events = $manager->getRelated($venue, 'events');

    // assign a one-to-many relation to a venue.
    $manager->assignRelated($venue, 'events');

    // get each one-to-many relation for all events in a list.
    // this will return an array of arrays. the order corresponds
    // to the order of the events passed.
    $venues = $manager->getList('Venue');
    $events = $manager->getRelated($venues, 'events');
    foreach ($venues as $idx=>$v) {
        echo "Found ".count($events[$idx])." events for venue ".$v->venueId."\n";
    }

    // assign each one-to-many relation to all venues in a list
    $venues = $manager->getList('Venue');
    $manager->assignRelated($venues, 'events');
    foreach ($venues as $idx=>$v) {
        echo "Found ".count($v->events)." events for venue ".$v->venueId."\n";
    }


Modifying
---------

See :doc:`modifying` for more info.

Modifying by object:

.. code-block:: php

    <?php
    // inserting an object:
    $event = new Event;
    $event->setName('Abc Def');
    $event->startDate = '2020-01-01';
    $manager->insert($event);
    
    // updating an existing object:
    $event = $manager->getByPk('Event', 1);
    $event->startDate = '2020-01-02';
    $manager->update($event);

    // using the 'save' method if the object contains an autoincrement primary:
    $event = new Event;
    // ...
    $manager->save($event);

    $event = $manager->getByPk('Event', 1);
    $event->startDate = '2020-01-02';
    $manager->save($event);


Modifying by table:

.. code-block:: php

    <?php
    // insert a new object
    $manager->insert('Event', array(
        'name'=>'Abc Def',
        'slug'=>'abc-def',
        'startDate'=>'2020-01-01',
    );

    // update by table. this can work on an arbitrary number of rows, depending on the condition
    $manager->update('Event', array('name'=>'Abc: Def'), 'startDate>?', '2019-01-01');

