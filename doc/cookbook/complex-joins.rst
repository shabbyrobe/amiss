Complex joins using VIEWs
=========================

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
        
        /** @has one of=Event; on=eventId */
        public $event;
    }


Then you can select away!

.. code-block:: php

    <?php
    $list = $manager->getList('EventArtistSummary', 'eventId=?', $eventId);
    $manaager->getRelated($list, 'event');

