Quick Start
===========

This quickstart will assume you wish to use an annotation-based mapper. See
:doc:`mapper/mapping` for more details and alternatives.


Install
-------

Add *Amiss* and all of its dependencies to your project using `Composer
<http://getcomposer.org>`_::

    composer require shabbyrobe/amiss


Loading and Configuring
-----------------------

See :doc:`configuring` and :doc:`mapper/mapping` for more details.

.. code-block:: php
    :testgroup: quickstart
    
    <?php
    // Include and register autoloader (generate using composer install)
    require 'vendor/autoload.php';
   
    // Amiss depends on the PDOK library (http://github.com/shabbyrobe/pdok).
    $connector = new PDOK\Connector('mysql:host=127.0.0.1', 'user', 'password');
    
    // This will create a SQL manager using the default configuration (note mapper, 
    // default types and relators, no cache)
    $config = [
        // app timezone is required if you want date/datetime/timestamp types
        'appTimeZone' => 'UTC',
    ];
    $manager = Amiss\Sql\Factory::createManager($connector, $config);
    
    // Same as above, but with a cache
    $cache = new \Amiss\Cache('apc_fetch', 'apc_store');
    $manager = Amiss\Sql\Factory::createManager($connector, $config + ['cache'=>$cache]);
    
    // Replace the default note mapper with a different mapper:
    $manager = Amiss\Sql\Factory::createManager($connector, [
        'mapper' => new \Amiss\Mapper\Local(),
        'appTimeZone' => 'UTC'
    ]);
    
    // Or go lean and mean, don't use any defaults at all and set up your
    // own mapper (Boolean_ is not a typo):
    $mapper = new \Amiss\Mapper\Local();
    $mapper->addTypeHandler(new \Amiss\Sql\Type\Autoinc, 'autoinc');
    $mapper->addTypeHandler(new \Amiss\Sql\Type\Boolean_, 'bool');
   
    $manager = new \Amiss\Sql\Manager($connector, $mapper);
    $manager->relators['one']  = new \Amiss\Sql\Relator\OneMany($manager);
    $manager->relators['many'] = new \Amiss\Sql\Relator\OneMany($manager);


Annotation Syntax
-----------------

*Amiss* uses the `Nope <http://github.com/shabbyrobe/nope>`_ library for
annotation support. Annotations in *Nope* have a very simple syntax. They follow
this format and MUST be embedded in doc block comments (``/** */``, not ``/*
*/``):

.. code-block:: nope

    /**
     * :namespace = {"json": "object"};
     */

All Amiss annotations use the ``:amiss`` namespace.

Annotations can span an arbitrary number of lines. Parsing ends when a semicolon is
encountered as the last non-whitespace character on a line:

.. code-block:: nope

    /**
     * :namespace = {
     *     "json": "object", 
     *     "yep": [1, 2, 3]
     * };
     */


Defining objects
----------------

Table names are guessed from the object name. Object names are converted from
``CamelCase`` to ``under_scores`` by default.

Table field names are taken from the property name. No name mapping is performed by
default, but you can pass an explicit field name via the ``field`` annotation, or pass
your own automatic translator to ``Amiss\Mapper\Base->unnamedPropertyTranslator``.

See :doc:`mapper/mapping` for more details and alternative mapping options.

.. code-block:: php
    :testgroup: quickstart
    
    <?php
    /** :amiss = true; */
    class Event
    {
        /**
         * The "autoinc" type handler will come pre-configured if you use the
         * Amiss\Sql\Factory::createManager(...) method.
         *
         * :amiss = {"field": {"type": "autoinc", "primary": true}};
         */
        public $eventId;
   
        /**
         * This is just a plain old field. Amiss * will not handle the field's
         * type - it will be treated as a string in * both directions.
         * 
         * :amiss = {"field": true};
         */
        public $name;
   
        /**
         * :amiss = {"field": {"type": "datetime"}};
         */
        public $dateStart;
   
        /**
         * This field contains an ID for a related object, so an index is required.
         * The index name is taken from the property name when the index is specified
         * in this way, so in this case it will be "venueId"
         *
         * :amiss = {"field": {"index": true}};
         */
        public $venueId;
   
        /**
         * Simple relationship - an event has one venue. "one" relations are
         * specified "from" an index on the current model "to" an index on the
         * related model. In this case the "venueId" index declared above relates
         * to the primary key on the Venue model.
         *
         * :amiss = {"has": {"type": "one", "of": "Venue", "from": "venueId"}};
         */
        public $venue;
    }
   
    /**
     * Explicit table name annotation. Leave this out and the table 
     * name will default to 'venue'
     *
     * :amiss = {"table": "venues"};
     */
    class Venue
    {
        /**
         * An index with the name "primary" is automatically defined for a
         * primary key.
         *
         * :amiss = {"field": {"type": "autoinc", "primary": true}};
         */
        public $venueId;
   
        /** :amiss = {"field": "venueName"}; */
        public $name;
   
        /** :amiss = {"field": true}; */
        public $slug;
   
        /** :amiss = {"field": true}; */
        public $address;
   
        /** 
         * Inverse relationship of Event->venue
         *
         * :amiss = {"has": {"type": "many", "of": "Event", "inverse": "venue"}};
         */
        public $events;
    }


Creating Tables
---------------

See :doc:`schema` for more details.

.. code-block:: php
    :testgroup: quickstart
    
    <?php
    // single
    Amiss\Sql\TableBuilder::create($connector, $manager, 'Venue');
   
    // multiple
    Amiss\Sql\TableBuilder::create($connector, $manager, ['Venue', 'Event']);
   
    // get the SQL for your own nefarious purposes:
    $query   = Amiss\Sql\TableBuilder::createSQL($connector, $manager, 'Venue');
    $queries = Amiss\Sql\TableBuilder::createSQL($connector, $manager, ['Venue', 'Event']);


Selecting
---------

See :doc:`selecting` for more details.

.. code-block:: php
    :testgroup: quickstart
    
    <?php
    // Get a single event by primary key
    $event = $manager->getById('Event', 1);
   
    // Get a single event by name using a raw SQL clause and positional parameters. 
    // Property names wrapped in curly braces get translated to field names by 
    // the mapper:
    $event = $manager->get(Event::class, '{name}=?', ['foobar']);
   
    // Get a single event by start date using a raw SQL clause and named parameters. 
    // In addition to field name unwrapping, if the named parameter names match a 
    // property name in your model, type handling is also performed:
    $event = $manager->get(
        'Event', 
        '{dateStart} = :dateStart', 
        ['dateStart'=>new \DateTime('2020-06-02')]
    );
    
    // Get all events
    $events = $manager->getList('Event');
   
    // Get all events named foo that start on the 2nd of June, 2020 using an array
    // clause. Array clauses are combined using "AND", must be keyed by property name,
    // and type handling is performed on values:
    $events = $manager->getList('Event', [
        'where' => ['name'=>'foo', 'dateStart'=>new \DateTime('2020-06-02')]
    ]);
   
    // Get all events with 'foo' in the name using positional parameters
    $events = $manager->getList('Event', [
        'where'  => '{name} LIKE ?', 
        'params' => ['%foo%']
    ]);
    
    // Paged list, limit/offset
    $events = $manager->getList('Event', [
        'where'  => '{name}=?',
        'params' => ['foo'],
        'limit'  => 10, 
        'offset' => 30
    ]);
   
    // Paged list, alternate style (number, size)
    $events = $manager->getList('Event', [
        'where'  => '{name}=?',
        'params' => ['foo'],
        'page'   => [1, 30]
    ));
   
    // Amiss will unroll and properly parameterise IN() clauses when using
    // named parameter clauses:
    $events = $manager->getList('Event', '{eventId} IN (:foo)', ['foo'=>[1, 2, 3]]);
   
    // IN() clauses are also generated when using array clauses:
    $events = $manager->getList('Event', ['where' => ['foo' => [1, 2, 3]]]);
   
    // FOR UPDATE InnoDB row locking
    $manager->connector->beginTransaction();
    $rows = $manager->getList('Event', array(
        'where'=>'...',
        'forUpdate'=>true,
    ));
    $manager->connector->commit();


Relations
---------

Amiss supports one-to-one, one-to-many and many-to-many relations, and provides
an extension point for adding additional relationship retrieval methods. See
:doc:`relations` for more details.


One-to-one
~~~~~~~~~~

.. code-block:: php
   
    <?php
    /** :amiss = true; */
    class Event
    {
        /** :amiss = {"field": {"primary": true}}; */
        public $eventId;
   
        /** :amiss = {"field": {"index": true}}; */
        public $venueId;
        
        // snip
   
        /**
         * :amiss = {"has": {"type": "one", "of": "Venue", "from": "venueId"}};
         */
        public $venue;
    }

.. code-block:: php
    :testgroup: quickstart  
   
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
        /** :amiss = {"field": {"primary": true}}; */
        public $venueId;
        
        // snip
   
        /**
         * :amiss = {"has": {"type": "many", "of": "Event", "to": "venueId"}};
         */
        public $events;
    }

.. code-block:: php
    :testgroup: quickstart
    
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

Many-to-many relations require the association table to be mapped to an intermediate
object, and also require the relation to be specified on both sides:

.. code-block:: php
    
    <?php
    class Event
    {
        /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
        public $eventId;
   
        /** :amiss = {"has": {"type": "assoc", "of": "Artist", "via": "EventArtist"}}; */
        public $artists;
    }
   
    class EventArtist
    {
        /** :amiss = {"field": {"index": true}}; */
        public $eventId;
   
        /** :amiss = {"field": {"index": true}}; */
        public $artistId;
   
        /** :amiss = {"has": {"type": "one", "of": "Event", "from": "eventId"}}; */
        public $event;
   
        /** :amiss = {"has": {"type": "one", "of": "Artist", "from": "artistId"}}; */
        public $artist;
    }
   
    class Artist
    {
        /** :amiss = {"field": {"primary": true}}; */
        public $artistId;
        
        /** :amiss = {"has": {"type": "assoc", "of": "Event", "via": "EventArtist"}}; */
        public $events;
    }

.. code-block:: php
    :testgroup: quickstart
 
    <?php
    $event = $manager->getById('Event', 1);
    $artists = $manager->getRelated($event, 'artists');


Modifying
---------

You can modify by object or by table. See :doc:`modifying` for more details.

Modifying by object:

.. code-block:: php
    :testgroup: quickstart
    
    <?php
    // Inserting an object:
    $event = new Event;
    $event->setName('Abc Def');
    $event->dateStart = new \DateTime('2020-01-01');
    $manager->insert($event);
    
    // Updating an existing object:
    $event = $manager->getById('Event', 1);
    $event->dateStart = new \DateTime('2020-01-02');
    $manager->update($event);
   
    // Using the 'save' method (insert if new, otherwise update):
    $event = new Event;
    $manager->save($event); // inserts
    $event->dateStart = new \DateTime('2020-01-02');
    $manager->save($event); // update


Modifying by table:

.. code-block:: php
    :testgroup: quickstart
    
    <?php
    // Insert a new row using property names (type handling is performed)
    $manager->insertTable('Event', array(
        'name'=>'Abc Def',
        'slug'=>'abc-def',
        'dateStart'=>new \DateTime('2020-01-01'),
    );
   
    // Update by table.
    // 
    // This can work on an arbitrary number of rows, depending on the condition.
    // Clauses can be specified the same way as 'selecting'.
    // 
    // If the parameter name in the 'update' or 'set' clause matches a property
    // name in the model, type handling is performed
    $manager->updateTable(
        'Event', 
        ['name'=>'Abc: Def'],
        '{dateStart} > :dateStart',
        ['dateStart' => new \DateTime('2019-01-01')]
    );
    
    // Alternative clause syntax
    $manager->updateTable('Event', [
        'set'   => ['name' => 'Abc: Def'], 
        'where' => ['dateStart' => new \DateTime('2019-01-01')],
    ]);

