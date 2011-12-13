Relations
=========

Relations are handled as separate queries. 

Relation operations can return their value or assign it to a first-level child for you.

The ``getRelated`` method handles one-to-one relations and the ``getRelatedList`` method handles one-to-many. They have the following signatures::

    getRelated     ( $for , $type , $on )
    getRelatedList ( $for , $type , $on )


Parameters
----------

The $for parameter
~~~~~~~~~~~~~~~~~~

The ``$for`` parameter has two formats. The first is the entity for which you would like to retrieve a related entity (or an array of entities in the case of ``getRelatedList``):

.. code-block:: php

    <?php
    $parent = $amiss->get('Parent', 'parentId=?', 1);
    $child = $amiss->getRelated($parent, $type, $on);


The second is a 2-tuple where the first element of the tuple is the entity (or list) as per the first format, and the second element of the tuple is the property of the parent object to assign the related object to:

.. code-block:: php

    <?php
    $parent = $amiss->get('Parent', 'parentId=?', 1);
    $amiss->getRelated(array($parent, 'child'), $type, $on);
    var_dump($parent->child); // will equal the object retrieved by getRelated, if one is found


The $type parameter
~~~~~~~~~~~~~~~~~~~

The ``$type`` parameter simply represents the type to retrieve, as in the examples for ``$amiss->get()`` presented in the :doc:`selecting` section.


The $on parameter
~~~~~~~~~~~~~~~~~

The ``$on`` parameter contains the column, column mapping or list of columns/mappings to use for the join, like the "ON" clause in a SQL statement:

.. code-block:: sql

    SELECT * FROM artist a
    INNER JOIN artist_type at
    ON a.artistTypeId = at.artistTypeId


If the column names are the same in both tables, like the above example, it can be represented in Amiss like so:

.. code-block:: php

    <?php
    $artists = $amiss->get('Artist');
    $amiss->getRelated(array($artists, 'artistType'), 'ArtistType', 'artistTypeId');


If the column names are different, like this ON clause:

.. code-block:: sql
    
    SELECT ... INNER JOIN ... ON a.pantsTypeId = at.artistTypeId


The $on parameter should be passed as an array:

.. code-block:: php

    <?php
    $artists = $amiss->get('Artist');
    $amiss->getRelated(array($artists, 'artistType'), 'ArtistType', array('pantsTypeId'=>'artistTypeId'));


If there are many columns to join on (pardon the contrived example), you can mix and match both of the previous ``$on`` examples as needed:

.. code-block:: php

    <?php
    $foobar = $amiss->get('FooBar');
    $amiss->getRelated(array($foobar, 'child'), 'FooBarChild', array('fooBar', 'fooBaz'=>'fooQux'));


Selecting
---------

Selecting 1-to-1
~~~~~~~~~~~~~~~~

Retrieving a single related object:

.. code-block:: php

    <?php
    $eventArtist = $amiss->get('EventArtist', 'eventId=? AND artistId=?', $eventId, $artistId);
    $event = $amiss->getRelated($eventArtist, 'Event', 'eventId');


Assigning a single related object:

.. code-block:: php

    <?php
    $eventArtist = $amiss->get('EventArtist', 'eventId=? AND artistId=?', $eventId, $artistId);
    $amiss->getRelated(array($eventArtist, 'event'), 'Event', 'eventId');
    // $eventArtist->event will contain the related object


Assigning a single related object to a list. Each ``EventArtist`` in the ``$eventArtists`` list will have its related ``Artist`` retrieved by ``getRelated`` and assigned to the ``artist`` property:

.. code-block:: php

    <?php
    $eventArtists = $amiss->getList('EventArtist', 'eventId=?', $eventId);
    $amiss->getRelated(array($eventArtist, 'artist'), 'Artist', 'artistId');



Selecting 1-to-n
~~~~~~~~~~~~~~~~

Retrieving a list of related objects:

.. code-block:: php

    <?php
    $event = $amiss->get('Event', 'eventId=?', $eventId);
    $eventArtists = $amiss->getRelatedList($event, 'EventArtist', 'eventId');


Assigning a list of related objects:

.. code-block:: php

    <?php
    $event = $amiss->get('Event', 'eventId=?', $eventId);
    $amiss->getRelatedList(array($event, 'eventArtists'), 'EventArtist', 'eventId');


Assigning a related list to each entry in a list. Each ``ArtistType`` in the ``$types`` list will have its related ``Artists`` retrieved by ``getRelatedList`` and assigned as an array to the ``artists`` property:

.. code-block:: php

    <?php
    $types = $amiss->getList('ArtistType');
    $amiss->getRelatedList(array($types, 'artists'), 'Artist', 'artistTypeId');


Assigning Nested Relations
~~~~~~~~~~~~~~~~~~~~~~~~~~

What about when we have a list of ``Events``, we have retrieved each related list of ``EventArtist``, and we want to assign the related ``Artist`` to each ``EventArtist``? And what if we want to take it one step further and assign each ``ArtistType`` too?

Easy! We can use ``Amiss\Manager->getChildren()`` for our evil bidding.

Before we go any further, let's recap our relation graph: 

1. ``Event`` has many ``EventArtist``
2. ``EventArtist`` has one ``Artist``
3. ``Artist`` has one ``ArtistType``

.. code-block:: php
    
    <?php
    $events = $amiss->getList('Event');
    
    // Relation 1: populate each Event object's list of EventArtists
    $amiss->getRelatedList(array($events, 'eventArtists'), 'EventArtist', 'eventId');
    
    // Relation 2: populate each EventArtist object's artist property
    $amiss->getRelated(array($amiss->getChildren($events, 'eventArtists'), 'artist'), 'Artist', 'artistId');
    
    // Relation 3: populate each Artist object's artistType property
    $amiss->getRelated(array($amiss->getChildren($events, 'eventArtists/artist'), 'artistType'), 'ArtistType', 'artistTypeId');


Woah, what just happened there? We used ``getChildren`` to build us an array of each child object contained in the list of parent objects. The first line shows we have a list of ``Event`` objects::

    $events = $amiss->getList('Event');

We populate Relation 1 as described in the previous section on "Selecting"::

    $amiss->getRelatedList(array($events, 'eventArtists'), 'EventArtist', 'eventId');

And then things get kooky when we populate Relation 2. Unrolled, the Relation 2 call looks like this::

    // Relation 2: populate each EventArtist object's artist property
    $eventArtists = $amiss->getChildren($events, 'eventArtists');
    $amiss->getRelated(array($eventArtists, 'artist'), 'Artist', 'artistId');

The first call - to ``getChildren`` - iterates over the ``$events`` array and gathers every child ``EventArtist`` into an array, which it then returns. We can then rely on the fact that PHP `passes all objects by reference <http://php.net/manual/en/language.oop5.references.php>`_ and just use this array as the argument to the next ``getRelated`` call.

Relation 3 gets kookier still by adding nesting to the ``getChildren`` call. Here it is unrolled::

    $artists = $amiss->getChildren($events, 'eventArtists/artist');
    $amiss->getRelated(array($artists, 'artistType'), 'ArtistType', 'artistTypeId');

The second argument to ``getChildren`` in the above example is not just one property, it's a path. It essentially says 'for each event, get each event artist from the eventArtists property, then aggregate each artist from the event artist's artist property and return it. So you end up with a list of every single ``Artist`` attached to an ``Event``. The call to ``getRelated`` then goes and fetches the ``ArtistType`` objects that correspond to each ``Artist`` and assigns it.


Using joins
-----------

Firstly, create a MySQL view with your joins:

.. code-block:: sql
    
    CREATE VIEW event_artist_summary AS 
        SELECT e.eventId, a.artistId, a.artistTypeId, a.artistName, ea.priority, ea.sequence
        FROM event_artist ea
        INNER JOIN artist a
        ON a.artistId = ea.artistId


Secondly, create an object to represent the row:

.. code-block:: php

    <?php
    class EventArtistSummary
    {
        public $eventId;
        public $artistId;
        public $artistTypeId;
        
        public $type;
        public $name;
        public $priority;
        public $sequence;
        
        /**
         * @var Event
         */
        public $event;
    }

.. note::

    it will eventually be possible to use a subclass of an existing type for this to mitigate the need for an extra object.


Then you can select away!

.. code-block:: php

    <?php
    $list = $amiss->getList('EventArtistSummary', 'eventId=?', $eventId);
    $amiss->getRelated(array($list, 'event'), 'Event', 'eventId');

