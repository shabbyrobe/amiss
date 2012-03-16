Relations
=========

An object's relations are determined by your mapping configuration. See :doc:`mapping` for more details on how to configure your relations. At a glance, when using ``Amiss\Mapper\Note``, you would define a bi-directional relation like so:

.. code-block:: php

    <?php
    class Artist {
        /** @primary */
        public $artistId;
        
        /** @field */
        public $artistTypeId;
        
        /** @has one ArtistType artistTypeId */  
        public $artistType;
    }

    class ArtistType {
        /** @primary */
        public $artistTypeId;

        /** @field */
        public $type;

        /** @has many Artist */
        public $artists = array();
    }


Retrieving
----------

Amiss provides relation retrieval for one-to-one relations and one-to-many relations by default. You can add extra relationships if you need them. See *Adding Relators* below. One-to-one and one-to-many relations are handled as separate queries.

Amiss provides two methods for retrieving and populating relations:

.. py:function:: getRelated($source, $relationName)

    :param source: The single object or array of objects for which to retrieve the related values
    :param relationName: The name of the relation through which to retrieve objects

    Retrieves and returns objects related to the ``$source`` through the ``$relationName``:

    .. code-block:: php

        <?php
        $artist = $manager->getByPk('Artist', 1);
        $type = $manager->getRelated($artist, 'artistType');


    You can also retrieve the relation for every object in a list. The returned array will be indexed using the same keys as the input source.

    .. code-block:: php

        <?php
        $artists = $manager->getList('Artist');
        $types = $manager->getRelated($artists, 'artistType');
        
        $artists[0]->artistType = $types[0];
        $artists[1]->artistType = $types[1];
    

.. py:function:: assignRelated($into, $relationName)

    :param into: The single object or array of objects into which this will set the related values
    :param relationName: The name of the relation through which to retrieve objects

    The ``assignRelated`` method will call ``getRelated`` and assign the resulting relations to the source object(s):

    .. code-block:: php

        <?php
        $artist = $manager->getByPk('Artist', 1);
        $manager->assignRelated($artist, 'artistType');
        $type = $artist->artistType;
    

    You can also assign the related values for every object in a list:

    .. code-block:: php

        <?php
        $artists = $manager->getList('Artist');
        $manager->assignRelated($artists, 'artistType');
        echo $artists[0]->artistType->type;
        echo $artists[1]->artistType->type;


Assigning Nested Relations
~~~~~~~~~~~~~~~~~~~~~~~~~~

What about when we have a list of ``Events``, we have retrieved each related list of ``EventArtist``, and we want to assign the related ``Artist`` to each ``EventArtist``? And what if we want to take it one step further and assign each ``ArtistType`` too?

Easy! We can use ``Amiss\Manager->getChildren()``.

Before we go any further, let's outline a relation graph present in the ``doc/demo/model.php`` file:

1. ``Event`` has many ``EventArtist``
2. ``EventArtist`` has one ``Artist``
3. ``Artist`` has one ``ArtistType``

.. code-block:: php
    
    <?php
    $events = $manager->getList('Event');
    
    // Relation 1: populate each Event object's list of EventArtists
    $manager->assignRelated($events, 'eventArtists');
    
    // Relation 2: populate each EventArtist object's artist property
    $manager->assignRelated($manager->getChildren($events, 'eventArtists'), 'artist');
    
    // Relation 3: populate each Artist object's artistType property
    $manager->assignRelated($manager->getChildren($events, 'eventArtists/artist'), 'artistType');


Woah, what just happened there? We used ``getChildren`` to build us an array of each child object contained in the list of parent objects. The first line shows we have a list of ``Event`` objects::

    $events = $manager->getList('Event');

We populate Relation 1 as described in the previous section on "Selecting"::

    $manager->assignRelated($events, 'eventArtists');

And then things get kooky when we populate Relation 2. Unrolled, the Relation 2 call looks like this::

.. code-block:: php

    <?php
    // Relation 2: populate each EventArtist object's artist property
    $eventArtists = $manager->getChildren($events, 'eventArtists');
    $manager->assignRelated($eventArtists, 'artist');


The first call - to ``getChildren`` - iterates over the ``$events`` array and gathers every child ``EventArtist`` into an array, which it then returns. We can then rely on the fact that PHP `passes all objects by reference <http://php.net/manual/en/language.oop5.references.php>`_ and just use this array as the argument to the next ``assignRelated`` call.

Relation 3 gets kookier still by adding nesting to the ``getChildren`` call. Here it is unrolled:

.. code-block:: php

    <?php
    $artists = $manager->getChildren($events, 'eventArtists/artist');
    $manager->assignRelated($artists, 'artistType');


The second argument to ``getChildren`` in the above example is not just one property, it's a path. It essentially says 'for each event, get each event artist from the eventArtists property, then aggregate each artist from the event artist's artist property and return it. So you end up with a list of every single ``Artist`` attached to an ``Event``. The call to ``getRelated`` then goes and fetches the ``ArtistType`` objects that correspond to each ``Artist`` and assigns it.


Using complex joins
-------------------

TODO: move this to a cookbook section

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
        /** @field */
        public $eventId;

        /** @field */
        public $artistId;

        /** @field */
        public $artistTypeId;
        
        /** @field */
        public $type;

        /** @field */
        public $name;

        /** @field */
        public $priority;

        /** @field */
        public $sequence;
        
        /** @has one Event eventId */
        public $event;
    }


Then you can select away!

.. code-block:: php

    <?php
    $list = $manager->getList('EventArtistSummary', 'eventId=?', $eventId);
    $manaager->getRelated($list, 'event');


Adding Relators
---------------

You can add your own relationship types to Amiss by creating your own ``Relator`` class and adding it to the ``Amiss\Manager->relators`` array. It must contain the following method:

.. py:method:: getRelated($manager, $type, $source, $relationName)

    :param manager: ``Amiss\Manager`` instance calling your relator. You'll need this to do queries.
    :param type: The type of relation. 
    :param source: The source object(s). This could be either a single object or an array of objects depending on your context. You are free to raise an exception if your ``Relator`` only supports single objects or arrays
    :param relationName: The name of the relation which was passed to ``getRelated``


You can register your relator with Amiss like so:

.. code-block:: php

    <?php
    $manager->relators['one-to-foo'] = new My\Custom\OneToFooRelator;


If you are using ``Amiss\Mapper\Note``, you would define a relation that uses this relator like so:

.. code-block:: php

    class Bar
    {
        /** @primary */
        public $id

        /** @has one-to-foo blah blah */
        public $foo;
    }

Calls to ``getRelated`` and ``assignRelated`` referring to ``Bar->foo`` will now use your custom relator to retrieve the related objects.

