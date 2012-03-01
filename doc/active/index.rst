Active Records
==============

From `P of EAA`_:
An object that wraps a row in a database table or view, encapsulates the database access, and adds domain logic on that data.

.. _`P of EAA`: http://martinfowler.com/eaaCatalog/activeRecord.html

I'm not in love with this pattern, but I have used it in the past with some other libraries. This has been added to facilitate a migration for an old project of mine, but people seem to be quite fond of Active Records so why not include it.

``Amiss\Active\Record`` is an Active Record wrapper around ``Amiss\Manager``. It's not fancy, it's not good, it's not fully-featured, but it does seem to work OK for the quick-n-dirty ports I've done.

It does place the following constraints:

* All Active Records must have an autoincrement primary key if you want to use the ``save`` method. If not, you'll still be able to use ``insert`` and ``update``.
* Class Hierarchies that use a separate connection must declare a base class.


It will benefit you tremendously to understand how the :doc:`/mapper/index` works before reading these documents.

.. toctree::
    :maxdepth: 1

    quickstart

.. toctree::
    :maxdepth: 2

    defining
    connecting
    selecting
    relations
    saving
    schema
