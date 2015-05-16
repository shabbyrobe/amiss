Selecting
=========

``Amiss\Sql\Manager`` has three methods for retrieving objects: ``getById``, ``get`` and
``getList``.  Both methods share the same set of signatures, and they can both be used in
a number of different ways. The first argument is always the model name. All the
subsequent arguments are used to define criteria for the query.

The selection methods are:

.. py:method:: Amiss\\Sql\\Manager::getById( $model , $primaryKeyValue )

Retrieve a single instance of ``$model`` represented by ``$primaryKeyValue``:

    .. code-block:: php
    
        <?php
        $event = $manager->getById('Event', 5);
        
    If the primary key is a multi-column primary key, you can pass an array containing the
    values in the same order as the metadata defines the primary key's properties:

    .. code-block:: php
    
        <?php
        $eventArtist = $manager->getById('EventArtist', array(2, 3));
    
    If you find the above example to be a bit unreadable, you can use the property names
    as keys:

    .. code-block:: php
    
        <?php
        $eventArtist = $manager->getById('EventArtist', array('eventId'=>2, 'artistId'=>3));


.. py:method:: object Amiss\\Sql\\Manager::get( $model , $criteria... )

    Retrieve a single instance of ``$model``, determined by ``$criteria``. This will throw
    an exception if the criteria you specify fails to limit the result to a single object.

    .. code-block:: php

        <?php
        $event = $manager->get('Venue', '{venueSlug}=?', array($slug));

    See :ref:`clauses` and :ref:`criteria-arguments` for more details.


.. py:method:: array Amiss\\Sql\\Manager::getList( $mode , $criteria... )

    Retrieve a list of instances of ``$model``, determined by ``$criteria``. Exactly the
    same as ``get``, but allows you to find many objects and will always return an array.


.. _clauses:

Clauses
-------

This represents the ``where`` part of your query.

Most ``where`` clauses in Amiss can be written by hand in the underlying DB server's
dialect. This allows complex expressions with an identical amount of flexibility to using
raw SQL - because it *is* raw SQL.

All ``Amiss\Sql\Manager->get...()`` methods can accept clauses as part of their criteria.
When passing a clause as a string, you can pass it using the underlying table's column
names:

.. code-block:: php

    <?php
    // The Artist class has a property called 'artistTypeId' that maps to a 
    // column with the same name:
    $artists = $manager->getList('Artist', 'artistTypeId=?', array(1));


When your table's column names are exactly the same as your property names, this is the
way you should do it - there's no sense in making Amiss do more work than it needs to -
but when your column names are different, Amiss will perform a simple token replacement on
your clause, converting ``{propertyName}`` into the ``column_name`` from the underlying
metadata:

.. code-block:: php

    <?php
    // The Venue class has a property called 'venueName' that maps to a column
    // called 'venue_name'
    $venue = $manager->get('Venue', '{venueName}=?', array('foo'));

In the above example, ``{venueName}`` is replaced with the field ``venue_name``, resulting
in the following query::

    SELECT * FROM venue WHERE venue_name='foo'


You can also pass an array of values indexed by property name for the where clause if you
are using an ``Amiss\Sql\Query\Criteria`` (or a criteria array). This type of clause will
perform field mapping without the need for curly braces. Multiple key/value pairs in the
'where' array are treated as an ``AND`` query:

.. code-block:: php

    <?php
    $venues = $manager->getList(
        'Venue',
        array('where'=>array('venueName'=>'Foo', 'venueSlug'=>'foo'))
    );
    // WHERE venue_name='Foo' AND venue_slug='foo'


.. _criteria-arguments:

Criteria Arguments
------------------

Several methods throughout this documentation take a dynamic argument list referred to as
``$criteria...``. This is always accepted at the end of the argument list and can be
passed in a number of different formats. The ``get()`` and ``getList()`` methods of
``Amiss\Sql\Manager`` take their criteria after the the ``$modelName`` argument, whereas
``getRelated()`` takes it after both the ``$modelName`` and the ``$relationName``
arguments.

Please also familiarise yourself with the section on :ref:`clauses` before diving in.


Shorthand
~~~~~~~~~

The "where" clause and parameters can be passed using a shorthand format that consists of
a SQL expression with positional PDO-style placeholders (question marks) and each
corresponding value in an array::

    ( $criteria... ) == ( string $positionalWhere, [scalar $param... ] )

.. code-block:: php

    <?php
    $badNews = $manager->get('Event', 'name=? AND slug=?', array('Bad News', 'bad-news-2'));
    $bands = $manager->getList('Artist', 'artistTypeId=1');


To select using named placeholders, pass the where clause as the first criteria argument
and an array of parameters the next argument::

    ( $criteria... ) == ( string $namedWhere, array $params )

.. code-block:: php

    <?php
    $duke = $manager->get('Artist', 'slug=:slug', array(':slug'=>'duke-nukem'));


Long form
~~~~~~~~~

The long form of query criteria is either an array representation of the relevant
``Amiss\Sql\Query\Criteria`` derivative, or an actual instance thereof::

    ( $criteria... ) == ( array $criteria )
    ( $criteria... ) == ( Amiss\Sql\Query\Criteria $criteria )


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
    $criteria = new Amiss\Sql\Query\Select;
    $criteria->where = 'slug=:slug';
    $criteria->params[':slug'] = 'duke-nukem';
    
    $artist = $manager->get('Artist', $criteria);


Lists
-----

The ``getList()`` method will return every row in the Artist table if no criteria are
passed (be careful!):

.. code-block:: php

    <?php
    $artists = $manager->getList('Artist');


In addition to the "where" clause and parameters, ``getList()`` will also make use of
additional criteria:


Pagination
~~~~~~~~~~

Amiss provides two ways to perform pagination. The first is the standard LIMIT/OFFSET
combo:

.. code-block:: php

    <?php
    // limit to 30 rows
    $artists = $manager->getList('Artist', array('limit'=>30);

    // limit to 30 rows, skip 60
    $artists = $manager->getList('Artist', array('limit'=>30, 'offset'=>60));


The second style is suited to the way your UI typically thinks of pagination: using page
number/page size. This is passed as a :term:`2-tuple` using the ``page`` key:

.. code-block:: php

    <?php
    // retrieve page 1, page size 30. equivalent to LIMIT 30
    $artists = $manager->getList('Artist', array('page'=>array(1, 30)));

    // retrieve page 3, page size 30. equivalent to LIMIT 30, OFFSET 60
    $artists = $manager->getList('Artist', array('page'=>array(3, 30)));


Ordering
~~~~~~~~

There are several different ways to order your results. 

You can order ascending on a single column with the following shorthand. Fields will be
mapped using this method:

.. code-block:: php

    <?php
    $eventArtists = $manager->getList('EventArtist', array('order'=>'priority'));


Just like :ref:`clauses`, you can order using an array. The key should be the field name,
which *will* be mapped in this case, and the value should be the order direction. The
default order direction is ascending, so if you wish to sort ascending you can either
specify 'asc' directly, or just omit the key and pass the field name as the value.

This will produce the same order as the previous example:

.. code-block:: php

    <?php
    $eventArtists = $manager->getList('EventArtist', array(
        'order'=>array(
            'priority'=>'desc',
            'sequence',
        ),
    ));


And also like :ref:`clauses`, you can write your order expression in raw sql. You can use
column names directly, or you can use property name placeholders:

.. code-block:: php

    <?php
    $eventArtists = $manager->getList('EventArtist', array(
        'order'=>'{propertyName} desc, column_name',
    ));


Counting
--------

You can use all of the same signatures that you use for ``Amiss\Sql\Manager->get()`` to
count rows:

.. code-block:: php

    <?php
    // positional parameters
    $dukeCount = $manager->count('Artist', '{slug}=?', array('duke-nukem'));

    // named parameters, shorthand:
    $dukeCount = $manager->count('Artist', '{slug}=:slug', array(':slug'=>'duke-nukem'));

    // long form
    $criteria = new \Amiss\Sql\Query\Criteria();
    $criteria->where = '{slug}=:slug';
    $criteria->params = array(':slug'=>'duke-nukem');
    $dukeCount = $manager->count('Artist', $criteria);


"In" Clauses
------------

Vanilla PDO statements with parameters don't work with arrays and IN clauses:

.. code-block:: php

    <?php
    // This won't work.
    $pdo = new PDO(...);
    $stmt = $pdo->prepare("SELECT * FROM bar WHERE foo IN (:foo)");
    $stmt->bindValue(':foo', array(1, 2, 3));
    $stmt->execute(); 


Amiss handles unrolling non-nested array parameters:

.. code-block:: php

    <?php 
    $criteria = new Amiss\Sql\Query\Criteria;
    $criteria->where = 'foo IN (:foo)';
    $criteria->params = array(':foo'=>array(1, 2));
    $criteria->namedParams = true;
    list ($where, $params) = $criteria->buildClause();
    
    echo $where;        // foo IN (:foo_0,:foo_1) 
    var_dump($params);  // array(':foo_0'=>1, ':foo_1'=>2)


You can use this with ``Amiss\Sql\Manager`` easily:

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

    Do not mix and match hand-interpolated query arguments and "in"-clause parameters (not
    that you should be doing this anyway). The following example may not work quite like
    you expect:

    .. code-block:: php

        <?php
        $criteria = new \Amiss\Sql\Query\Criteria;
        $criteria->params = array(
            ':foo'=>array(1, 2),
            ':bar'=>array(3, 4),
        );
        $criteria->where = 'foo IN (:foo) AND bar="hey IN(:bar)"';
        
        list ($where, $params) = $criteria->buildClause();
        echo $where;
    
    You'd be forgiven for assuming that the output would be::

        foo IN(:foo_0,:foo_1) AND bar="hey IN(:bar)"
    
    However, the output will actually be::
        
        foo IN(:foo_0,:foo_1) AND bar="hey IN(:bar_0,:bar_1)"

    This is because Amiss does no parsing of your WHERE clause. It does a fairly naive
    regex substitution that is more than adequate if you heed this warning.


Constructor Arguments
---------------------

If you are mapping an object that requires constructor arguments, you can pass them using
criteria.

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
    $manager->getById('Foo', 1, array(new Bar));

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


``FOR UPDATE``
--------------

If you are using InnoDB and wish to select rows using ``FOR UPDATE``, you can set the
``forUpdate`` key of the criteria to ``true``. Make sure you're inside a transaction:

.. code-block:: php

    <?php
    $manager->connector->beginTransaction();
    
    // all of these rows will now have row level locks
    $rows = $manager->getList('Pants', [
        'where'=>'{pantsTypeId}=1',
        'forUpdate'=>true,
    ]);

    // there are better ways to do this, it just illustrates the locking example
    foreach ($rows as $pants) {
        $pants->counter++;
        $manager->update($pants); 
    }

    $manager->connector->commit();

