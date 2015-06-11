Overview
========

MongoDB is document oriented, and the PHP extension belches complex, multi-dimensional arrays intermixed with Mongo's wrapper types for values that it can't accurately represent using raw PHP types:

.. code-block:: php

    <?php
    $mongo = new \Mongo();
    $db = $mongo->dbname;
    $result = $db->event->findOne(array('_id'=>new \MongoId('...')));

    // result will equal this:
    $result = array (
        '_id' => new \MongoId('4f8c21a1b1e69a9b04000000'),
        'name' => 'AwexxomeFest',
        'slug' => 'awexxome-fest',
        'dateStart' => new \MongoDate(1334583713, 0),
        'dateEnd' => new \MongoDate(1334583713, 0),
        'venue' => array(
            '_id' => new \MongoId('4f8c21a1b1e69a9b04000001'),
            'name' => 'Strong Badia',
            'slug' => 'strong-badia',
            'address' => 'The field behind the dumpsters, Free-Country USA',
        ),
    );


This extension leverages the mappers used by Amiss for relational SQL databases to transform these documents into domain objects:

.. code-block:: php

    <?php
    $mapper = new \Amiss\Mapper\Note();
    $mapper->addTypeSet(new \Amiss\Mongo\TypeSet());

    $mongo = new \Mongo();
    $db = $mongo->dbname;
    $result = $mapper->mapObjectToRow(
        $mapper->getMeta('Event'),
        $db->event->findOne(array('_id'=>new \MongoId('...')))
    );
    var_dump($result);


This will have the following output::

    Event#1 (
        [eventId] => '4f8c1f8fb1e69a8e04000000'
        [Event:name] => 'AwexxomeFest'
        [Event:subName] => null
        [Event:slug] => 'awexxome-fest'
        [dateStart] => DateTime#1 (
            [date] => '2012-04-16 13:33:03'
            [timezone_type] => 1
            [timezone] => '+00:00'
        )
        [dateEnd] => DateTime#2 (
            [date] => '2012-04-16 13:33:03'
            [timezone_type] => 1
            [timezone] => '+00:00'
        )
        [venue] => Venue#1 (
            [venueId] => '4f8c1f8fb1e69a8e04000001'
            [venueName] => 'Strong Badia'
            [venueSlug] => 'strong-badia'
            [venueAddress] => 'The field behind the dumpsters, Free-Country USA'
            [venueShortAddress] => null
            [venueLatitude] => null
            [venueLongitude] => null
        )
    )

