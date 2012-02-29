Saving
======

The following methods are available for saving::

    Amiss\Active\Record -> save ( )
    Amiss\Active\Record -> insert ( )
    Amiss\Active\Record -> update ( )
    Amiss\Active\Record -> update ( string $positionalWhere, [ mixed $param1, ... ] )
    Amiss\Active\Record -> update ( string $namedWhere, array $params )

The ``save()`` and ``update()`` methods only work if there is a primary key specified against the Active Record, however ``insert()`` and the ``update(...)`` methods with where clauses will work if there is not.


Hooks
-----

You can define additional behaviour against your Active Record which will occur when certain events happen inside Amiss.

The ``Amiss\Active\Record`` class defines the following hooks in addition to the ones defined by ``Amiss\Manager``. I sincerely hope these are largely self explanatory:

* ``beforeInsert()``
* ``beforeUpdate()``
* ``beforeSave()``
* ``beforeDelete()``
    
.. note:: ``beforeSave()`` is called when an item is inserted *or* updated. It is called in addition to ``beforeInsert()`` and ``beforeUpdate()``.

ALWAYS call the parent method of the hook when overriding:

.. code-block:: php

    <?php
    class MyRecord extends \Amiss\Active\Record
    {
        // snipped fields, etc

        function beforeUpdate()
        {
            parent::beforeUpdate();
            // do your own stuff here
        }
    }

