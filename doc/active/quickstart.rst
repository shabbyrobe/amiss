Quick Start
===========

.. contents::


Importing and defining connections
----------------------------------

See :doc:`connecting` for more info.

.. code-block:: php

    <?php

    require_once('/path/to/amiss/src/Loader.php');
    spl_autoload_register(array(new Amiss\Loader, 'load'));

    Amiss\Active\Record::setManager(
        new Amiss\Manager(new Amiss\Connector('mysql:host=127.0.0.1', 'user', 'password'));
    );


Defining records
----------------

See :doc:`defining` for more info and advanced topics.

.. code-block:: php

    <?php

    class Venue extends \Amiss\Active\Record
    {
        // explicit table name. leave off and the table will be assumed to be `venue`
        public static $table = 'venues';
        
        public static $fields = array(
            'venueId', 'name', 'slug', 'address', 'shortAddress'
        );

        public static $relations = array(
            // "has many" relation
            // in spite of Event defining the other side of this relation, 
            // bi-directional relations are not implied
            'events'=>array('many'=>'Event', 'on'=>'venueId'),
        );
    }


Creating Tables
---------------

See :doc:`schema` for more info.

.. code-block:: php

    <?php

    $tableBuilder = new Amiss\Active\TableBuilder('Venue');
    $tableBuilder->createTable();


Selecting
---------

See :doc:`selecting` for more info.

.. code-block:: php

    <?php
    // get venue by primary key
    Venue::getByPk(1);

    // get a venue named foobar
    Venue::get('name=?', 'foobar');

    // get all venues
    Venue::getList();

    // get all venues named foo
    Venue::getList('name=?', 'foo');

    // get all venues with 'foo' contained in the name, positional parameters
    Venue::getList(array('where'=>'name LIKE ?', 'params'=>array('%foo%')));

    // get all venues with 'foo' contained in the name, named parameters
    Venue::getList(array('where'=>'name LIKE :foo', 'params'=>array(':foo'=>'%foo%')));

    // paged list, limit/offset
    Venue::getList(array('where'=>'name="foo"', 'limit'=>10, 'offset'=>30));

    // paged list, alternate style (number, size)
    Venue::getList(array('where'=>'name="foo"', 'page'=>array(1, 30)));


