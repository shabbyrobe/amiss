Saving
======

The following methods are available for saving::

    Amiss\Active\Record -> save ( )
    Amiss\Active\Record -> insert ( )
    Amiss\Active\Record -> update ( )
    Amiss\Active\Record -> update ( string $positionalWhere, [ mixed $param1, ... ] )
    Amiss\Active\Record -> update ( string $namedWhere, array $params )

The ``save()`` and ``update()`` methods only work if there is a primary key specified against the Active Record, however ``insert()`` and the ``update(...)`` methods with where clauses will work if there is not.

