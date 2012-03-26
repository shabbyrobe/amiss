Selecting
=========

``Amiss\Manager`` has two methods for handling object retrieval: ``get`` and ``getList``. Both methods share the same set of signatures, and they can both be used in a number of different ways::

    get ( string $modelName, string $positionalWhere, mixed $param1[, mixed $param2...])
    get ( string $modelName, string $namedWhere, array $params )
    get ( string $modelName, array $criteria )
    get ( string $modelName, Amiss\Criteria\Select $criteria )


The parameters are as follows:

	.. attribute:: $model
	
	    The model to retrieve from the database
	    
	
	.. attribute:: $positionalWhere / $namedWhere
	
	    The SQL "where" clause, written in the server's dialect. Positional "where" clauses use ``?`` for parameter substitution while named "where" clauses use ``:param`` style tokens.
	      
	
	.. attribute:: $criteria
	
	    An ``Amiss\Criteria\Select`` instance, or an array that can be converted into an ``Amiss\Criteria\Select`` instance.


Single Objects
--------------

Single objets are retrieved using the ``get`` method. This is designed to retrieve only one object - it will throw an exception if more than one row is found.


Single object using positional parameters, shorthand
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $duke = $manager->get('Artist', 'slug=?', 'duke-nukem');


Single object with named parameters, shorthand
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $duke = $manager->get('Artist', 'slug=:slug', array(':slug'=>'duke-nukem'));


Single object using named parameters, long form
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $artist = $manager->get(
        'Artist', 
        array(
            'where'=>'slug=:slug', 
            'params'=>array(':slug'=>'duke-nukem')
        )
    );


Single object using an Amiss\Criteria object
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

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

This will return every row in the Artist table (careful!):

.. code-block:: php

    <?php
    $artists = $manager->getList('Artist');


Paged List
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

You can use all of the same signatures that you use for ``get`` to count rows (excluding LIMITs, of course):


Count using positional parameters, shorthand
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $dukeCount = $manager->count('Artist', 'slug=?', 'duke-nukem');


Count using named parameters, shorthand
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $dukeCount = $manager->count('Artist', 'slug=:slug', array(':slug'=>'duke-nukem'));


Count using named parameters, long form
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $artistCount = $manager->count(
        'Artist', 
        array(
            'where'=>'slug=:slug', 
            'params'=>array(':slug'=>'duke-nukem')
        )
    );


Count using an Amiss\Criteria object
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php
    $criteria = new Amiss\Criteria\Select;
    $criteria->where = 'slug=:slug';
    $criteria->params[':slug'] = 'duke-nukem';
    
    // this is detected when using other methods
    $criteria->namedParams = true;
    
    $count = $manager->count('Artist', $criteria);


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


Clauses
-------

The "where" clause is written by hand in the underlying DB server's dialect. This allows complex expressions with an identical amount of flexibility to using raw SQL - because it *is* raw SQL. The tradeoff is that your clauses may not necessarily be portable.

Ultimately, this kind of means you don't really save too much code when selecting with Amiss, but have you ever met a developer who didn't go the long way to avoid doing something they hate?

This is a stupid query, but it does illustrate what this aspect will let you get away with:

.. code-block:: php
    
    <?php
    $artists = $manager->getList(
        'Artist', 
        'artistTypeId=:foo AND artistId IN (SELECT artistId FROM event_artist WHERE eventId=:event)', 
        array(':foo'=>1, ':event'=>5)
    );
    

You can also just specify an array for the where clause if you are passing in an ``Amiss\Criteria\Query`` (or a criteria array):

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

