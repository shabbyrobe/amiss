Complex joins using VIEWs
=========================

Amiss doesn't do any joins at all internally, but sometimes you may want to represent a complex join
without writing a huge repository class full of SQL and still use Amiss' interface. In that
circumstance, you can use a SQL view.

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
        /**
         * :amiss = {"field":true};
         */
        public $eventId;

        /**
         * :amiss = {"field":true};
         */
        public $artistId;

        /**
         * :amiss = {"field":true};
         */
        public $artistTypeId;
        
        /**
         * :amiss = {"field":true};
         */
        public $type;

        /**
         * :amiss = {"field":true};
         */
        public $name;

        /**
         * :amiss = {"field":true};
         */
        public $priority;

        /**
         * :amiss = {"field":true};
         */
        public $sequence;
        
        /**
         * :amiss = {
         *     "has": {
         *         "type": "one",
         *         "of": "Event",
         *         "on": "eventId"
         *     }
         * };
         */
        public $event;
    }


Then you can select away!

.. code-block:: php

    <?php
    $list = $manager->getList('EventArtistSummary', 'eventId=?', $eventId);
    $manaager->getRelated($list, 'event');

