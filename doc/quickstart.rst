Quick Start
===========

This quickstart will assume you wish to use an annotation-based mapper. See :doc:`mapper/mapping` for more details and alternatives.


Loading and Configuring
-----------------------

See :doc:`configuring` and :doc:`mapper/mapping` for more details.

.. code-block:: php

    <?php

    // Include and register autoloader (optional)
    require_once('/path/to/amiss/src/Loader.php');
    Amiss\Loader::register();

    // Amiss requires a class that implements \Amiss\Mapper in order to get information
    // about how your objects map to tables
    $mapper = new Amiss\Mapper\Note;

    // This is basically a PDO with a bit of extra niceness. You should use it instead
    // of PDO in your own code
    $connector = new Amiss\Connector('mysql:host=127.0.0.1', 'user', 'password');

    // And this binds the whole mess together
    $manager = new Amiss\Manager($connector, $mapper);


Defining objects
----------------

See :doc:`mapper/mapping` for more details and alternative mapping options.

.. code-block:: php

    <?php

    class Event
    {
        /** 
         * @primary 
         * @type autoinc
         */
        public $eventId;

        /** @field */
        public $name;

        /** @field */
        public $startDate;

        /** @field */
        public $venueId;

        /** @has one of=Venue; on=venueId */
        public $venue;
    }

    /**
     * Explicit table name annotation. Leave this out and the table will be assumed
     * to be 'venue'
     * @table venues
     */
    class Venue
    {
        /**
         * @primary
         * @type autoinc
         */
        public $venueId;

        /** @field venueName */
        public $name;

        /** @field */
        public $slug;

        /** @field */
        public $address;

        /** @has many of=Event */
        public $events;
    }


Creating Tables
---------------

See :doc:`schema` for more details.

.. code-block:: php

    <?php
    $tableBuilder = new Amiss\TableBuilder($manager, 'Venue');
    $tableBuilder->createTable();


Selecting
---------

See :doc:`selecting` for more details.

.. code-block:: php

    <?php
    // get an event by primary key
    $event = $manager->getByPk('Event', 1);

    // get an event named foobar. clauses are written in raw SQL.
    $event = $manager->get('Event', 'name=?', 'foobar');

    // get all events
    $events = $manager->getList('Event');

    // get all events named foo that start on the 2nd of June, 2020 using an array
    $events = $manager->getList('Event', array(
        'where'=>array('name'=>'foo', 'startDate'=>'2020-06-02')
    ));

    // get all events with 'foo' in the name using positional parameters
    $events = $manager->getList('Event', array(
        'where'=>'name LIKE ?', 
        'params'=>array('%foo%')
    ));
    
    // paged list, limit/offset
    $events = $manager->getList('Event', array(
        'where'=>'name="foo"', 
        'limit'=>10, 
        'offset'=>30
    ));

    // paged list, alternate style (number, size)
    $events = $manager->getList('Event', array(
        'where'=>'name="foo"', 
        'page'=>array(1, 30)
    ));


Relations
---------

Amiss supports one-to-one, one-to-many and many-to-many relations, and provides an extension point for adding additional relationship retrieval methods. See :doc:`relations` for more details.

One-to-one
~~~~~~~~~~

.. code-block:: php

    <?php
    class Event
    {
        // snip

        /**
         * @has one of=Venue; on=venueId
         */
        public $venue;
    }
    
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


One-to-many
~~~~~~~~~~~

.. code-block:: php

    <?php
    class Venue
    {
        // snip

        /**
         * @has many of=Event
         */
        public $events;
    }

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


Many-to-many
~~~~~~~~~~~~

Many-to-many relations require the association table to be mapped to an intermediate object, and also require the relation to be specified on both sides:


.. code-block:: php

    <?php
    class Event
    {
        // snip
        
        /**
         * @has assoc of=Artist; via=EventArtist
         */
        public $artists;
    }

    class EventArtist
    {
        // snip

        /**
         * @has one of=Event; on=eventId
         */
        public $event;

        /**
         * @has one of=Artist; on=artistId
         */
        public $artist;
    }

    class Artist
    {
        // snip

        /**
         * @has assoc of=Event; via=EventArtist
         */
        public $events;
    }

    $event = $manager->getByPk('Event', 1);
    $artists = $manager->getRelated($event, 'artists');


Modifying
---------

You can modify by object or by table. See :doc:`modifying` for more details.

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

