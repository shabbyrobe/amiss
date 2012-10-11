Relations
=========

An object's relations are determined by your mapping configuration. 

See :doc:`mapper/mapping` for more details on how to configure your relations. At a glance, when
using ``Amiss\Mapper\Note``, you would define a bi-directional relation like so:

.. code-block:: php

    <?php
    class Artist
    {
        /** @primary */
        public $artistId;
        
        /** @field */
        public $artistTypeId;
        
        /** @has one of=ArtistType; on=artistTypeId */  
        public $artistType;
    }

    class ArtistType
    {
        /** @primary */
        public $artistTypeId;

        /** @field */
        public $type;g16

        /** @has many of=Artist */
        public $artists = array();
    }


.. _relators:

Relators
--------

``Amiss\Sql\Manager`` handles retrieving related objects using extensions called "Relators".

Amiss provides three relationship types out of the box: ``one``, ``many`` and ``assoc``. You can add
extra relationships if you need them. See :ref:`custom-relators` below.


.. note:: 

    The following describes the way the relations are structured *after* your mapper has done its
    business converting your mappings into :doc:`mapper/metadata`. Documentation for the specific
    mapper you have chosen will explain how to declare these structures in the format they expect.
    See :doc:`mapper/mapping` for more details.

Relation retrieval using these relators is handled using separate queries.


.. _relator-one:

One-to-one Relator
~~~~~~~~~~~~~~~~~~

The ``one`` relationship specifies a one-to-one object relationship between the mapped object and
the object specified in the relation.

Using the example at the top of this document, we will follow the owning object ``Artist``'s
``artistType`` relation. The metadata definition for this relation looks like this:

.. code-block:: php

    <?php
    $relation = array('one', 'of'=>'ArtistType', 'on'=>'artistTypeId');

The ``of`` key defines the destination object of the relation. 

The ``on`` key defines the property name(s) that define the ID of the related object. This can be a
single string if the name is the same on both objects::

    'on'=>'artistTypeId',

An array of strings if the related object's primary key is composite::
    
    'on'=>array('artistTypeIdPartOne', 'artistTypeIdPartTwo')

Or an array of key=>value pairs when the related object's primary key has a different name to the
owning object's property::

    'on'=>array('artistTypeId'=>'id')
    

.. _relator-many:

Many-to-many Relator
~~~~~~~~~~~~~~~~~~~~

The ``many`` relationship specifies a one-to-many object relationship between the mapped object and
the object specified in the relation.

Using the example at the top of this document, we will follow the owning object ``ArtistType``'s
``artists`` relation.

The :doc:`metadata <mapper/metadata>` definition for a one-to-many relation looks like this:

.. code-block:: php

    <?php
    $relation = array('many', 'of'=>'Artist', 'on'=>'artistTypeId');

The ``of`` key defines the destination object of the relation. 

The ``on`` key defines the property name(s) that define the ID of the related object. The structure
is quite similar to the ``on`` key of the ``one`` relationship, but the primary key belongs to the
mapped object rather than the related one.

The ``on`` key is *optional* when specifying a ``many`` relation - the primary key of the owning
object is inferred if it is omitted.

``on`` can be a single string if the name is the same on both objects::

    'on'=>'artistTypeId',

An array of strings if the related object's primary key is composite and the names are the same on
both objects::
    
    'on'=>array('artistTypeIdPartOne', 'artistTypeIdPartTwo')

Or an array of key=>value pairs when the owning object's primary key has a different name to the
related object's property::

    'on'=>array('id'=>'artistTypeId')


.. _relator-assoc:

Association Relator
~~~~~~~~~~~~~~~~~~~

The ``assoc`` relationship specifies a many-to-many object relationship between the mapped object
and the object specified in the relation.

This mapping must be performed *via* an object that maps the association table to an object.

Consider a cut down version of the ``Event`` to ``Venue`` example:

.. code-block:: php

    <?php
    class Event
    {
        public $id;
        public $name;

        public $venues;
    }

    class Venue
    {
        public $id;
        public $name;

        public $events;
    }

``Event`` and ``Venue`` share a many-to-many relationship. This relationship is performed using an
association table called ``event_venue``. In order to use the assoc mapper, ``event_venue`` must 
also have an object that is mapped:

.. code-block:: php

    <?php
    class EventVenue
    {
        public $eventId;
        public $venueId;
    }


The :doc:`metadata <mapper/metadata>` definition for ``Event``'s many-to-many relation to ``Venue``
looks like this:

.. code-block:: php

    <?php
    $event->relations = array(
        'venues'=>array('assoc', 'of'=>'Venue', 'via'=>'EventVenue'),
    );

.. note:: ``EventVenue`` in this example *must itself be mapped*.


Retrieving Related Objects
--------------------------

Amiss provides two methods for retrieving and populating relations:

.. py:function:: Amiss\\Sql\\Manager::getRelated( $source , $relationName , $criteria ... )

    :param source: The single object or array of objects for which to retrieve the related values
    :param relationName: The name of the relation through which to retrieve objects
    :param criteria: *Optional*. Allows filtering of the related objects.

    Retrieves and returns objects related to the ``$source`` through the ``$relationName``:

    .. code-block:: php

        <?php
        $artist = $manager->getById('Artist', 1);
        $type = $manager->getRelated($artist, 'artistType');


    You can also retrieve the relation for every object in a list. The returned array will be
    indexed using the same keys as the input source.

    .. code-block:: php

        <?php
        $artists = $manager->getList('Artist');
        $types = $manager->getRelated($artists, 'artistType');
        
        $artists[0]->artistType = $types[0];
        $artists[1]->artistType = $types[1];

    
    The optional query argument is dynamic much the same as it is when :doc:`selecting`. Please read
    the sections on :ref:`criteria-arguments` and :ref:`clauses` for a thorough explanation on what
    ``getRelated()`` will accept for ``$criteria``. Here's a quick example:

    .. code-block:: php

        <?php
        $artistType = $manager->getById('ArtistType', 1);
        $artists = $manager->getRelated($artistType, 'artists', 'name LIKE ?', '%foo%');


.. py:function:: Amiss\\Sql\\Manager::assignRelated( $into , $relationName )

    :param into: The single object or array of objects into which this will set the related values
    :param relationName: The name of the relation through which to retrieve objects

    The ``assignRelated`` method will call ``getRelated`` and assign the resulting relations to the
    source object(s):

    .. code-block:: php

        <?php
        $artist = $manager->getById('Artist', 1);
        $manager->assignRelated($artist, 'artistType');
        $type = $artist->artistType;
    

    You can also assign the related values for every object in a list:

    .. code-block:: php

        <?php
        $artists = $manager->getList('Artist');
        $manager->assignRelated($artists, 'artistType');
        echo $artists[0]->artistType->type;
        echo $artists[1]->artistType->type;
    

    .. note:: ``assignRelated`` does not support filtering by query as it doesn't make sense. If you
    .. disagree, feel free to just do this:
        
        .. code-block:: php

            <?php
            $object->property = $manager->getRelated($object, 'foo', $query);


.. _relations-assigning-nested:

Assigning Nested Relations
--------------------------

What about when we have a list of ``Events``, we have retrieved each related list of
``EventArtist``, and we want to assign the related ``Artist`` to each ``EventArtist``? And what if
we want to take it one step further and assign each ``ArtistType`` too?

Easy! We can use ``Amiss\Sql\Manager->getChildren()``.

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
    $manager->assignRelated(
        $manager->getChildren($events, 'eventArtists'), 
        'artist'
    );
    
    // Relation 3: populate each Artist object's artistType property
    $manager->assignRelated(
        $manager->getChildren($events, 'eventArtists/artist'), 
        'artistType'
    );

    // this will show an ArtistType instance
    var_dump($events->eventArtists[0]->artist->artistType);


Woah, what just happened there? We used ``getChildren`` to build us an array of each child object
contained in the list of parent objects. The first line shows we have a list of ``Event`` objects::

    $events = $manager->getList('Event');

We populate Relation 1 as described in the previous section on retrieving::

    $manager->assignRelated($events, 'eventArtists');

And then things get kooky when we populate Relation 2. Unrolled, the Relation 2 call looks like this:

.. code-block:: php

    <?php
    // Relation 2: populate each EventArtist object's artist property
    $eventArtists = $manager->getChildren($events, 'eventArtists');
    $manager->assignRelated($eventArtists, 'artist');


The first call - to :ref:`getChildren() <helpers-get-children>` - iterates over the ``$events``
array and gets every unique ``EventArtist`` assigned to the ``Event->eventArtists`` property. We can
then rely on the fact that PHP `passes all objects by reference
<http://php.net/manual/en/language.oop5.references.php>`_ and just use this array as the argument to
the next ``assignRelated`` call.

Relation 3 gets kookier still by adding nesting to the ``getChildren`` call. Here it is unrolled:

.. code-block:: php

    <?php
    $artists = $manager->getChildren($events, 'eventArtists/artist');
    $manager->assignRelated($artists, 'artistType');


The second argument to ``getChildren`` in the above example is not just one property, it's a path.
It essentially says 'for each event, get each event artist from the eventArtists property, then
aggregate each artist from the event artist's artist property and return it. So you end up with a
list of every single ``Artist`` attached to an ``Event``. The call to ``getRelated`` then goes and
fetches the ``ArtistType`` objects that correspond to each ``Artist`` and assigns it.


.. _custom-relators:

Custom Relators
---------------

You can add your own relationship types to Amiss by creating a class that extends
``Amiss\Sql\Relator\Base`` and adding it to the ``Amiss\Sql\Manager->relators`` dictionary. Your Relator
must implement the following method:

.. py:method:: Amiss\\Sql\\Relator::getRelated( $source , $relationName , $criteria... = null )
    
    Retrieve the objects for the ``$source`` that are related through ``$relationName``. Optionally
    filter using ``$criteria``, which must be an instance of ``Amiss\Sql\Criteria\Query``.

    ``Amiss\Sql\Relator\Base`` makes an instance of ``Amiss\Sql\Manager`` available through
    ````$this->manager``. You can use this to perform queries.

    :param source: The source object(s). This could be either a single object or an array of objects 
        depending on your context. You are free to raise an exception if your ``Relator`` only 
        supports single objects or arrays.
    :param relationName: The name of the relation which was passed to ``getRelated``
    :param criteria: Optional filter criteria. Must be instance of ``Amiss\Sql\Criteria\Query``.


You can register your relator with Amiss like so:

.. code-block:: php

    <?php
    $manager->relators['one-to-foo'] = new My\Custom\OneToFooRelator($manager);


If you are using ``Amiss\Mapper\Note``, you would define a relation that uses this relator like so:

.. code-block:: php

    <?php
    class Bar
    {
        /** @primary */
        public $id

        /** @has one-to-foo blah blah */
        public $foo;
    }


Calls to ``getRelated()`` and ``assignRelated()`` referring to ``Bar->foo`` will now use your custom
relator to retrieve the related objects.

