Selecting
=========

``Amiss\Manager`` has two methods for handling object retrieval: ``get`` and ``getList``. Both methods share the same set of signatures, and they can both be used in a number of different ways::

    get ( string $model, string $positionalWhere, mixed $param1[, mixed $param2...])
    get ( string $model, string $namedWhere, array $params )
    get ( string $model, array $criteria )
    get ( string $model, Amiss\Criteria $criteria )


The parameters are as follows:

	.. attribute:: $model
	
	    The model to retrieve from the database
	    
	
	.. attribute:: $positionalWhere / $namedWhere
	
	    The SQL "where" clause, written in the server's dialect. Positional "where" clauses use ``?`` for parameter substitution while named "where" clauses use ``:param`` style tokens.
	      
	
	.. attribute:: $criteria
	
	    An Amiss\Criteria instance, or an array that can be converted into an Amiss\Criteria instance.
	    


Single Objects
~~~~~~~~~~~~~~

Single objets are retrieved using the ``get`` method. This is designed to retrieve only one object - it will throw an exception if more than one row is found.


Single object using positional parameters, shorthand
----------------------------------------------------


.. code-block:: html+php

    <?php
    $duke = $amiss->get('Artist', 'slug=?', 'duke-nukem');


Single object with named parameters, shorthand
----------------------------------------------

.. code-block:: html+php

    <?php
    $duke = $amiss->get('Artist', 'slug=:slug', array(':slug'=>'duke-nukem'));


Single object using named parameters, long form
-----------------------------------------------

.. code-block:: html+php

    <?php
    $artist = $amiss->get(
        'Artist', 
        array(
            'where'=>'slug=:slug', 
            'params'=>array(':slug'=>'duke-nukem')
        )
    );


Single object using an Amiss\Criteria object
--------------------------------------------

.. code-block:: html+php

    <?php
    $criteria = new Amiss\Criteria\Select;
    $criteria->where = 'slug=:slug';
    $criteria->params[':slug'] = 'duke-nukem';
    
    // this is detected when using other methods
    $criteria->namedParams = true;
    
    $artist = $amiss->get('Artist', $criteria);


Lists
~~~~~

This will return every row in the Artist table (careful!):

.. code-block:: html+php

    <?php
    $artists = $amiss->getList('Artist');


Paged List
----------

Retrieve page 1, page size 30:

.. code-block:: php

    <?php
    $artists = $amiss->getList('Artist', array('page'=>array(1, 30)));


Retrieve page 2, page size 30:

.. code-block:: php

    <?php
    $artists = $amiss->getList('Artist', array('page'=>array(2, 30)));


Limit to 30 rows, skip 60 (equivalent to "Retrieve page 3, page size 30"):

.. code-block:: php

    <?php
    $artists = $amiss->getList('Artist', array('limit'=>30, 'offset'=>60));


Limit to 30 rows:

.. code-block:: php

    <?php
    $artists = $amiss->getList('Artist', array('limit'=>30);


Ordering
--------

This will order by ``priority`` descending, then by ``sequence`` ascending:

.. code-block:: html+php
    
    <?php
    $eventArtists = $amiss->getList('EventArtist', array(
        'order'=>array(
            'priority'=>'desc',
            'sequence',
        ),
    ));


You can also order ascending on a single column with the following shorthand:

.. code-block:: php

    <?php
    $eventArtists = $amiss->getList('EventArtist', array('order'=>'priority'));


Clauses
~~~~~~~

The "where" clause is written by hand in the underlying DB server's dialect. This allows complex expressions with an identical amount of flexibility to using raw SQL - because it *is* raw SQL. The tradeoff is that your clauses may not necessarily be portable.

Ultimately, this kind of means you don't really save too much code when selecting with Amiss, but have you ever met a developer who didn't go the long way to avoid doing something they hate?

This is a stupid query, but it does illustrate what this aspect will let you get away with:

.. code-block:: php
    
    <?php
    $artists = $amiss->getList(
        'Artist', 
        'artistTypeId=:foo AND artistId IN (SELECT artistId FROM event_artist WHERE eventId=:event)', 
        array(':foo'=>1, ':event'=>5)
    );
    

You can also just specify an array for the where clause if you are passing in an ``Amiss\Criteria\Query`` (or a criteria array):

.. code-block:: php

    <?php
    $artists = $amiss->getList(
        'Artist',
        array('where'=>array('artistTypeId'=>1))
    );


"In" Clauses
------------

Vanilla PDO statements with parameters don't work with arrays and IN clauses:

.. code-block:: php

    <?php
    $pdo = new PDO(...);
    $stmt = $pdo->prepare("SELECT * FROM bar WHERE foo IN (:foo)");
    $stmt->bindValue(':foo', array(1, 2, 3));
    $stmt->execute(); 

BZZT! Nope. No workee.

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
    $artists = $amiss->getList(
        'Artist', 
        'artistId IN (:artistIds)', 
        array(':artistIds'=>array(1, 2, 3))
    );


.. note::

	This does not work with positional parameters (question-mark style).


Object Population Rules
~~~~~~~~~~~~~~~~~~~~~~~

Amiss will just throw each field into an object property with a similar name. It won't bother to check if it exists, it won't bother to check if it cares to receive it, it will just smash that value in regardless of whether it fits or not.

By default, any field name will have underscores stripped, and the character trailing an underscore will be uppercased. For example, the database field ``artist_name`` will be translated to the property ``artistName``.

This default behaviour will, like so many other aspects of Amiss, be fine and dandy for almost everything you'll ever do. 

But what about when it's not? There are two options.


Overriding the Default Name Mapper
----------------------------------


Custom Object Population
------------------------


