Quick Start
===========

This quickstart will assume you wish to use an annotation-based mapper. See :doc:`mapper/mapping`
for more details and alternatives.


Loading and Configuring
-----------------------

See :doc:`configuring` and :doc:`mapper/mapping` for more details.

.. code-block:: php

    <?php

    // Include and register optiona autoloader
    require_once('/path/to/amiss/src/Amiss.php');
    Amiss::register();

    // This is basically a PDO with a bit of extra niceness. You should use it instead
    // of PDO in your own code
    $connector = new Amiss\Sql\Connector('mysql:host=127.0.0.1', 'user', 'password');
    
    // This will create a SQL manager using the default configuration (note mapper, default types
    // and relators, no cache)
    $manager = Amiss::createSqlManager($connector);
    
    // Same as above, but with a cache
    $cache = new \Amiss\Cache('xcache_get', 'xcache_set');
    $manager = Amiss::createSqlManager($connector, array('cache'=>$cache));
    
    // Configure the default mapper
    $manager->mapper->objectNamespace = 'Your\Model';
    
    // Or use your own mapper:
    $mapper = new \Amiss\Mapper\Arrays();
    $manager = Amiss::createSqlManager($connector, $mapper); // shorthand
    $manager = Amiss::createSqlManager($connector, array(
        'mapper'=>$mapper, 
        'cache'=>$cache
    ));
    
    // Or use your own everything:
    $mapper = new \Amiss\Mapper\Arrays();
    $mapper->addTypeHandler(new \Amiss\Sql\Type\Autoinc, 'autoinc');
    $mapper->addTypeHandler(new \Amiss\Sql\Type\Bool, 'bool');
    $manager = new \Amiss\Sql\Manager($connector, $mapper);
    $manager->relators['one'] = new \Amiss\Sql\Relator\OneMany($manager);
    $manager->relators['many'] = new \Amiss\Sql\Relator\OneMany($manager);


Defining objects
----------------

Table names are guessed from the object name. Object names are converted from ``CamelCase`` to
``under_scores`` by default.

Table field names are guessed from the property name. No name mapping is performed by default, but
you can pass an explicit field name via the ``@field`` annotation, or pass your own automatic
translator to ``Amiss\Mapper\Base->unnamedPropertyTranslator``.

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

        /**
         * @has.one.of Venue
         * @has.one.on venueId
         */
        public $venue;
    }

    /**
     * Explicit table name annotation. Leave this out and the table will default to 'venue'
     * @table venues
     */
    class Venue
    {
        /**
         * @primary
         * @type autoinc
         */
        public $venueId;

        /**
         * @field venueName
         */
        public $name;

        /** @field */
        public $slug;

        /** @field */
        public $address;

        /** 
         * Inverse relationship of Event->venue
         * @has.many.of Event
         * @has.many.inverse venue
         */
        public $events;
    }


Creating Tables
---------------

See :doc:`schema` for more details.

.. code-block:: php

    <?php
    $tableBuilder = new Amiss\Sql\TableBuilder($manager, 'Venue');
    $tableBuilder->createTable();


Selecting
---------

See :doc:`selecting` for more details.

.. code-block:: php

    <?php
    // Get an event by primary key
    $event = $manager->getById('Event', 1);

    // Get an event named foobar with a clause written in raw SQL. Property names wrapped in
    // curly braces get translated to field names by the mapper.
    $event = $manager->get('Event', '{name}=?', 'foobar');

    // Get all events
    $events = $manager->getList('Event');

    // Get all events named foo that start on the 2nd of June, 2020 using an array
    $events = $manager->getList('Event', array(
        'where'=>array('name'=>'foo', 'startDate'=>'2020-06-02')
    ));

    // Get all events with 'foo' in the name using positional parameters
    $events = $manager->getList('Event', array(
        'where'=>'{name} LIKE ?', 
        'params'=>array('%foo%')
    ));
    
    // Paged list, limit/offset
    $events = $manager->getList('Event', array(
        'where'=>'{name}=?',
        'params'=>array('foo'),
        'limit'=>10, 
        'offset'=>30
    ));

    // Paged list, alternate style (number, size)
    $events = $manager->getList('Event', array(
        'where'=>'{name}=?',
        'params'=>array('foo'),
        'page'=>array(1, 30)
    ));

    // Amiss will unroll and properly parameterise IN() clauses
    $events = $manager->getList('Event', 'IN (:foo)', array('foo'=>array(1, 2, 3)));

    // FOR UPDATE InnoDB row locking
    $manager->connector->beginTransaction();
    $rows = $manager->getList('Event', array(
        'where'=>'...',
        'forUpdate'=>true,
    ));
    // do the update
    $manager->connector->commit();


Relations
---------

Amiss supports one-to-one, one-to-many and many-to-many relations, and provides an extension point
for adding additional relationship retrieval methods. See :doc:`relations` for more details.

One-to-one
~~~~~~~~~~

.. code-block:: php

    <?php
    class Event
    {
        /**
         * @primary
         * @field
         */
        public $eventId;
        
        // snip

        /**
         * @has.one.of Venue
         * @has.one.on venueId
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
        /**
         * @primary
         * @field
         */
        public $venueId;
        
        // snip

        /**
         * @has.many.of Event
         * @has.many.on venueId
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

Many-to-many relations require the association table to be mapped to an intermediate object, and
also require the relation to be specified on both sides:


.. code-block:: php

    <?php
    class Event
    {
        // snip
        
        /**
         * @has.assoc.of Artist
         * @has.assoc.via EventArtist
         */
        public $artists;
    }

    class EventArtist
    {
        // snip

        /**
         * @has.one.of Event
         * @has.one.on eventId
         */
        public $event;

        /**
         * @has.one.of Artist
         * @has.one.on artistId
         */
        public $artist;
    }

    class Artist
    {
        // snip

        /**
         * @has.assoc.of Event
         * @has.assoc.via EventArtist
         */
        public $events;
    }

    $event = $manager->getById('Event', 1);
    $artists = $manager->getRelated($event, 'artists');


Modifying
---------

You can modify by object or by table. See :doc:`modifying` for more details.

Modifying by object:

.. code-block:: php

    <?php
    // Inserting an object:
    $event = new Event;
    $event->setName('Abc Def');
    $event->startDate = '2020-01-01';
    $manager->insert($event);
    
    // Updating an existing object:
    $event = $manager->getById('Event', 1);
    $event->startDate = '2020-01-02';
    $manager->update($event);

    // Using the 'save' method if the object contains an autoincrement primary:
    $event = new Event;
    // ...
    $manager->save($event);

    $event = $manager->getById('Event', 1);
    $event->startDate = '2020-01-02';
    $manager->save($event);


Modifying by table:

.. code-block:: php

    <?php
    // Insert a new row
    $manager->insert('Event', array(
        'name'=>'Abc Def',
        'slug'=>'abc-def',
        'startDate'=>'2020-01-01',
    );

    // Update by table. Set the name field based on the start date.
    // This can work on an arbitrary number of rows, depending on the condition.
    // Clauses can be specified the same way as 'selecting'.
    $manager->update('Event', array('name'=>'Abc: Def'), '{startDate} > ?', '2019-01-01');
    
    // Alternative clause syntax
    $manager->update('Event', array(
        'set'=>array('name'=>'Abc: Def'), 
        'where'=>array('startDate'=>'2019-01-01')
    ));

