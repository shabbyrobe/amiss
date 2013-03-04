Configuring
===========

Autoloading
-----------

Amiss follows the :term:`PSR-0` standard and should work with any :term:`PSR-0` compatible
autoloader, but it also comes with its own classmap-based autoloader which you can use if you'd
prefer:

.. code-block:: php

    <?php
    $amissPath = '/path/to/amiss/src';
    require_once $amissPath.'/Amiss.php';
    Amiss::register();


Basics
------

The main class Amiss requires to do its business is ``Amiss\Sql\Manager``. It requires a way to
connect to the database and a class that can map your objects to the database and back.

.. warning:: 

    Amiss is built to support MySQL and SQLite **only**. It may work with other ANSI-compliant 
    RDBMSssseseses, but it is not tested or supported.


Creating an ``Amiss\Sql\Manager`` with the default mapping options is simple:

.. code-block:: php

    <?php
    $db = array(
        'dsn'=>'mysql:host=localhost;dbname=amiss_demo',
        'user'=>'user', 
        'password'=>'password',
    );
    $manager = new \Amiss::createSqlManager($db);


This will also create an instance of ``Amiss\Mapper\Note`` with a default set of :doc:`mapper/types`
and assign it to the manager. If you wish to use your own mapper, you can pass it as the second
argument to ``createSqlManager()``. The  mapper must implement the ``Amiss\Mapper`` interface.

.. code-block:: php

    <?php
    $mapper = new \Amiss\Mapper\Arrays();
    $manager = \Amiss::createSqlManager($db, $mapper);


If the default options are not desirable, you can create an instance of ``Amiss\Sql\Manager``
yourself by hand, though it will not come with any :ref:`relators` unless you add them:

.. code-block:: php

    <?php
    $manager = new \Amiss\Sql\Manager($db);
    $manager->relators = Amiss::createSqlRelators();


For more information on customising the mapping, please read the :doc:`mapper/mapping` section.


Options
-------

``Amiss::createSqlManager()`` accepts either an instance of ``Amiss\Mapper`` or an array of 
configuration options as the second parameter. The following options are supported:

.. py:attribute:: mapper

    An instance of ``Amiss\Mapper`` to use instead of the default mapper.
    
.. py:attribute:: cache

    An instance of ``Amiss\Cache`` to be used if the default ``mapper`` is used.

.. py:attribute:: typeHandlers

    If the default ``mapper`` is used, this can contain an array of ``Amiss\Type\Handler`` instances
    indexed by a string indicating the type name. Used instead of the default set of type handlers
    produced by ``Amiss::createSqlTypeHandlers``.

.. py:attribute:: relators

    An array of ``Amiss\Sql\Relator`` instances indexed by a string indicating the relation type.
    Used instead of the default set of relators produced by ``Amiss::createSqlRelators``.


``Amiss::createSqlTypeHandlers`` can returns handlers for converting database dates to PHP
``DateTime`` objects. For these conversions to happen consistently and reliably, both the
database timezone and the application timezone need to be specified in the config otherwise the
handlers will not be created:

.. py:attribute:: dbTimeZone

    The timezone used by the database. Can be a string or an instance of ``DateTimeZone``.
    
    See ``SELECT @@global.time_zone, @@session.time_zone;`` and
    <https://dev.mysql.com/doc/refman/5.5/en/time-zone-support.html>.

.. py:attribute:: appTimeZone

    The timezone used by the application. Can be a string or an instance of ``DateTimeZone``. To
    pass the default, assign the value of ``date_default_timezone_get()``.


Database Connections
--------------------

In addition to the array shown above, ``Amiss\Sql\Manager`` can also be passed an
``Amiss\Sql\Connector`` object. ``Amiss\Sql\Connector`` is a PDO_-compatible object with a few
enhancements. It takes the same constructor arguments, but it sets the error mode to
``PDO::ERRMODE_EXCEPTION`` by default.

Creating an instance of ``Amiss\Sql\Connector`` is the same as creating an instance of ``PDO``:

.. code-block:: php

    <?php
    $connector = new Amiss\Sql\Connector('mysql:host=localhost;', 'user', 'password');


You can also create an ``Amiss\Sql\Connector`` using an array containing the connection details:

.. code-block:: php

    <?php
    $connector = Amiss\Sql\Connector::create(array(
        'dsn'=>'mysql:host=localhost;dbname=amiss_demo',
        'user'=>'user', 
        'password'=>'password',
    ));

``create()`` is quite tolerant in what it accepts. You can pass it names that correspond to PDO's
constructor arguments dsn, user, password and options, as well as the non-standard host, server
and db... it'll even assume anything that starts with a u or a p corresponds to user and password
respectively.

``Amiss\Sql\Manager`` will also accept the same array as ``Amiss\Sql\Connector::create`` as a 
connection.

.. note:: 

    You *can* pass ``Amiss\Sql\Manager`` an instance of ``PDO``, or anything else that behaves like
    a ``PDO`` for that matter, though using ``Amiss\Sql\Connector`` instead is highly recommended as
    some features may not work exactly as expected.

    ``Amiss\Sql\Connector`` is PDO_-compatible so you can use it instead of ``PDO`` in your own 
    code, rather than so you can use a ``PDO`` with Amiss instead of an ``Amiss\Sql\Connector``.

    Just be aware that although ``Amiss\Sql\Connector`` shares 100% of the interface with PHP 5.3's
    PDO_, it does not derive from it. If you're using type hints like ``function foo(\PDO $pdo)`` it
    won't work.

    One critical difference between ``PDO`` and ``Amiss\Sql\Connector`` is that ``PDO`` will
    *connect to the database as soon as you instantiate it*. ``Amiss\Sql\Connector`` defers creating
    this connection until it is actually needed.


.. _PDO: http://www.php.net/manual/en/book.pdo.php


Connection Charset
~~~~~~~~~~~~~~~~~~

If you are using MySQL and you need to set the connection's charset, you can either use
``PDO::MYSQL_ATTR_INIT_COMMAND`` option or pass the ``connectionStatements`` key through to
``Amiss\Sql\Connector::create``.

Using ``PDO`` options:

.. code-block:: php

    <?php
    $connector = Amiss\Sql\Connector::create(array(
        'dsn'=>...,
        'options'=>array(
            \PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8',
        ),
    ));

Using ``connectionStatements``:

.. code-block:: php

    <?php
    $connector = Amiss\Sql\Connector::create(array(
        'dsn'=>...,
        'connectionStatements'=>array(
            'SET NAMES utf8',
        ),
    ));
