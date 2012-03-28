Selecting
=========

``Amiss\Manager`` has two methods for handling object retrieval: ``get`` and ``getList``. Both methods share the same set of signatures, and they can both be used in a number of different ways.

The first argument to ``get`` and ``getList`` is always the model name. All the subsequent arguments are used to define criteria for the query.

The selection methods are:

.. py:method:: Amiss\\Manager->getByPk( $model , $primaryKeyValue )

    Retrieve a single instance of ``$model`` represented by ``$primaryKeyValue``:

    .. code-block:: php
        
        <?php
        $event = $manager->getByPk('Event', 5);
    
    If the primary key is a multi-column primary key, you can pass an array containing the values in the same order as the metadata defines the primary key's properties:

    .. code-block:: php
    
        <?php
        $eventArtist = $manager->getByPk('EventArtist', array(2, 3));
    
    If you find the above example to be a bit unreadable, you can use the property names as keys:

    .. code-block:: php
    
        <?php
        $eventArtist = $manager->getByPk('EventArtist', array('eventId'=>2, 'artistId'=>3));


.. py:method:: Amiss\\Manager->get( $model , $criteria... )

    Retrieve a single instance of ``$model``, determined by ``$criteria``. This will throw an exception if the criteria you specify fails to limit the result to a single object.

    .. code-block:: php

        <?php
        $event = $manager->get('Venue', 'slug=?', $slug);

    See :ref:`clauses` and :ref:`criteria-arguments` for more details.


.. py:method:: Amiss\\Manager->getList( $mode , $criteria... )

    Retrieve a list of instances of ``$model``, determined by ``$criteria``. See :ref:`criteria-arguments` for more details on selecting the right object.


.. _clauses:

Clauses
-------

This represents the "WHERE" part of your query.

Most "where" clauses in Amiss will be written by hand in the underlying DB server's dialect. This allows complex expressions with an identical amount of flexibility to using raw SQL - because it *is* raw SQL. The tradeoff is that hand-written "where" clauses skip field mapping - you have to use the column name rather than the object property names.

This is a stupid query, but it does illustrate what this aspect will let you get away with:

.. code-block:: php
    
    <?php
    $artists = $manager->getList(
        'Artist',
        'artistTypeId=:foo AND artistId IN (SELECT artistId FROM event_artist WHERE eventId=:event)', 
        array(':foo'=>1, ':event'=>5)
    );
    

You can also just specify an array for the where clause if you are passing in an ``Amiss\Criteria\Query`` (or a criteria array). This method *will* perform field mapping. Multiple key/value pairs in the 'where' array are treated as an "AND" query:

.. code-block:: php

    <?php
    $artists = $manager->getList(
        'Artist',
        array('where'=>array('artistTypeId'=>1))
    );


"In" Clauses
~~~~~~~~~~~~

Vanilla PDO statements with parameters don't work with arrays and IN clauses:

.. code-block:: php

    <?php
    $pdo = new PDO(...);
    $stmt = $pdo->prepare("SELECT * FROM bar WHERE foo IN (:foo)");
    $stmt->bindValue(':foo', array(1, 2, 3));
    $stmt->execute(); 

BZZT! Nope.

Amiss handles unrolling non-nested array parameters:

.. code-block:: php

    <?php 
    $criteria = new Amiss\Criteria;
    $criteria->where = 'foo IN (:foo)';
    $criteria->params = array(':foo'=>array(1, 2));
    $criteria->namedParams = true;
    list ($where, $params) = $criteria->buildClause();
    
    echo $where;        // foo IN (:foo_0,:foo_1) 
    var_dump($params);  // array(':foo_0'=>1, ':foo_1'=>2)


You can use this with ``Amiss\Manager`` easily:

.. code-block:: php

    <?php
    $artists = $manager->getList(
        'Artist', 
        'artistId IN (:artistIds)', 
        array(':artistIds'=>array(1, 2, 3))
    );


.. note::

    This does not work with positional parameters (question-mark style).

.. warning::

    Do not mix and match hand-interpolated query arguments and "in"-clause parameters (not that you should be doing this anyway):

    .. code-block:: php

        <?php
        $criteria = new Criteria\Query;
        $criteria->params = array(
            ':foo'=>array(1, 2),
            ':bar'=>array(3, 4),
        );
        $criteria->where = 'foo IN (:foo) AND bar="hey IN(:bar)"';
        
        list ($where, $params) = $criteria->buildClause();
        echo $where;
    
    The output should be::

        foo IN(:foo_0,:foo_1) AND bar="hey IN(:bar)"
    
    However, the output will actually be::
        
        foo IN(:foo_0,:foo_1) AND bar="hey IN(:bar_0,:bar_1)"

    It's not pretty, but Amiss does not intend to babysit you so it's unlikely it will be fixed.



.. _criteria-arguments:

Criteria Arguments
------------------

Methods that accept query criteria do so at the end of the function signature. Query criteria can be passed in a number of different formats. The ``get()`` and ``getList()`` methods take their criteria after the the ``$modelName`` argument.

Amiss treats hand-written "where" clauses as raw SQL and performs no field mapping.


Shorthand
~~~~~~~~~

The "where" clause and parameters can be passed using a shorthand format. 

To select using positional placeholders, pass the where clause as the first criteria argument and each positional parameter as a subsequent argument.

.. code-block:: php

    <?php
    $badNews = $manager->get('Event', 'name=? AND slug=?', 'Bad News', 'bad-news-2');
    $bands = $manager->getList('Artist', 'artistTypeId=1');


To select using named placeholders, pass the where clause as the first criteria argument and an array of parameters the next argument:

.. code-block:: php

    <?php
    $duke = $manager->get('Artist', 'slug=:slug', array(':slug'=>'duke-nukem'));


Long form
~~~~~~~~~

The long form of query criteria is either an array representation of the relevant ``Amiss\Criteria\\Query`` derivative, or an actual instance thereof.

.. code-block:: php

    <?php
    $artist = $manager->get(
        'Artist', 
        array(
            'where'=>'slug=:slug', 
            'params'=>array(':slug'=>'duke-nukem')
        )
    );


.. code-block:: php

    <?php
    $criteria = new Amiss\Criteria\Select;
    $criteria->where = 'slug=:slug';
    $criteria->params[':slug'] = 'duke-nukem';
    
    // this is detected when using other methods
    $criteria->namedParams = true;
    
    $artist = $manager->get('Artist', $criteria);


Lists
-----

The ``getList()`` method will return every row in the Artist table if no criteria are passed (be careful!):

.. code-block:: php

    <?php
    $artists = $manager->getList('Artist');


In addition to the "where" clause and parameters, ``getList()`` will also make use of additional criteria:


Pagination
~~~~~~~~~~

Retrieve page 1, page size 30:

.. code-block:: php

    <?php
    $artists = $manager->getList('Artist', array('page'=>array(1, 30)));


Retrieve page 2, page size 30:

.. code-block:: php

    <?php
    $artists = $manager->getList('Artist', array('page'=>array(2, 30)));


Limit to 30 rows, skip 60 (equivalent to "Retrieve page 3, page size 30"):

.. code-block:: php

    <?php
    $artists = $manager->getList('Artist', array('limit'=>30, 'offset'=>60));


Limit to 30 rows:

.. code-block:: php

    <?php
    $artists = $manager->getList('Artist', array('limit'=>30);


Ordering
~~~~~~~~

This will order by ``priority`` descending, then by ``sequence`` ascending:

.. code-block:: php
    
    <?php
    $eventArtists = $manager->getList('EventArtist', array(
        'order'=>array(
            'priority'=>'desc',
            'sequence',
        ),
    ));


You can also order ascending on a single column with the following shorthand:

.. code-block:: php

    <?php
    $eventArtists = $manager->getList('EventArtist', array('order'=>'priority'));


Counting
--------

You can use all of the same signatures that you use for ``Amiss\Manager->get()`` to count rows:

.. code-block:: php

    <?php
    // positional parameters
    $dukeCount = $manager->count('Artist', 'slug=?', 'duke-nukem');

    // named parameters, shorthand:
    $dukeCount = $manager->count('Artist', 'slug=:slug', array(':slug'=>'duke-nukem'));

    // long form
    $criteria = new \Amiss\Criteria\Query();
    $criteria->where = 'slug=:slug';
    $criteria->params = array(':slug'=>'duke-nukem');
    $dukeCount = $manager->count('Artist', $criteria);


Constructor Arguments
---------------------

If you are mapping an object that requires constructor arguments, you can pass them using criteria.

.. code-block:: php
    
    <?php
    class Foo
    {
        /** @primary */
        public $id;

        public function __construct(Bar $bar)
        {
            $this->bar = $bar;
        }
    }

    class Bar {}

    // retrieving by primary with args
    $manager->getByPk('Foo', 1, array(new Bar));

    // retrieving single object by criteria with args
    $manager->get('Foo', array(
        'where'=>'id=?',
        'params'=>array(1),
        'args'=>array(new Bar)
    ));

    // retrieving list by criteria with args
    $manager->getList('Foo', array(
        'args'=>array(new Bar)
    ));


.. note:: Amiss does not yet support using row values as constructor arguments.

