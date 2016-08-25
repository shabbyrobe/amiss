Configuring
===========

Basics
------

The main class Amiss requires to do its business is ``Amiss\Sql\Manager``. It
requires a way to connect to the database and a class that can map your objects
to the database and back.

.. warning:: 

    Amiss is built to support MySQL and SQLite **only**. It may work with other
    ANSI-compliant RDBMSssseseses, but it is not tested or supported. Postgres
    support is being added, but has not been completed.


Creating an ``Amiss\Sql\Manager`` with the default mapping options is simple:

.. code-block:: php

    <?php
    $db = [
        'dsn'      => 'mysql:host=localhost;dbname=amiss_demo',
        'user'     => 'user', 
        'password' => 'password',
    ];
    $config = ['appTimeZone' => 'UTC'];
    $manager = \Amiss\Sql\Factory::createManager($db, $config);


This will also create an instance of ``Amiss\Mapper\Note`` with a default set of
:doc:`mapper/types` and assign it to the manager. If you wish to use your own
mapper, you can pass it in the config array. The mapper must implement the
``Amiss\Mapper`` interface.

.. code-block:: php

    <?php
    $config = [
        'date'   => ['dbTimeZone' => 'UTC'],
        'mapper' => new \Amiss\Mapper\Arrays(),
    ];
    $manager = \Amiss\Sql\Factory::createManager($db, $config);


If the default options are not desirable, you can create an instance of
``Amiss\Sql\Manager`` yourself by hand, though it will not come with any
:ref:`relators` or :doc:`mapper/types` (other than the default "everything is a
string" handling) unless you add them:

.. code-block:: php

    <?php
    $manager = new \Amiss\Sql\Manager($db);
    $manager->relators = \Amiss\Sql\Factory::createRelators();


For more information on customising the mapping, please read the
:doc:`mapper/mapping` section.


Options
-------

``Amiss\Sql\Factory::createManager()`` accepts an array of configuration options
as the second parameter. The following options are supported:

``mapper``:

    An instance of ``Amiss\Mapper`` to use instead of the default mapper.
    
``cache``:

    An instance of ``Amiss\Cache`` to be used if the default ``mapper`` is used.

``typeHandlers``:

    If the default ``mapper`` is used, this can contain an array of
    ``Amiss\Type\Handler`` instances indexed by a string indicating the type
    name. Used instead of the default set of type handlers produced by
    ``Amiss\Sql\Factory::createTypeHandlers``.

``relators``:

    An array of ``Amiss\Sql\Relator`` instances indexed by a string indicating
    the relation type.  Used instead of the default set of relators produced by
    ``Amiss\Sql\Factory::createRelators``.


``Amiss\Sql\Factory::createTypeHandlers`` returns handlers for converting
database dates to PHP ``DateTime`` objects. For these conversions to happen
consistently and reliably, both the database timezone and the application
timezone need to be specified in the config otherwise the handlers will not be
created:

``dbTimeZone``:

    The timezone used by the database. Can be a string or an instance of
    ``DateTimeZone``.
    
    See ``SELECT @@global.time_zone, @@session.time_zone;`` and
    <https://dev.mysql.com/doc/refman/5.5/en/time-zone-support.html>.

``attribute:: appTimeZone``:

    The timezone used by the application. Can be a string or an instance of
    ``DateTimeZone``. To pass the default, assign the value of
    ``date_default_timezone_get()``.


``Amiss\Sql\Factory::createTypeHandlers`` also creates a handler for Decimal
types. The defaults (used if none are defined on your field) can be set using
the ``decimalPrecision`` and ``decimalScale`` config parameters.


Database Connections
--------------------

*Amiss* uses `PDOK <http://github.com/shabbyrobe/pdok>`_ for database connection
handling.  *PDOK* is a simple drop-in replacement for PDO_.

``Amiss\Sql\Manager`` will accept an instance of ``PDOK\Connector``, or an array
of configuration options accepted by ``PDOK\Connector::create([...])``.
``PDOK\Connector`` is a PDO_-compatible object with a few enhancements: it takes
the same constructor arguments, but it sets the error mode to
``PDO::ERRMODE_EXCEPTION`` by default.

Creating an instance of ``PDOK\Connector`` is the same as creating an instance
of ``PDO``:

.. code-block:: php

    <?php
    $connector = new PDOK\Connector('mysql:host=localhost;', 'user', 'password');


You can also create an ``PDOK\Connector`` using an array containing the
connection details:

.. code-block:: php

    <?php
    $connector = PDOK\Connector::create([
        'dsn'      => 'mysql:host=localhost;dbname=amiss_demo',
        'user'     => 'user', 
        'password' => 'password',
    ]);

``create()`` is quite tolerant in what it accepts. You can pass it names that
correspond to the PDO_ constructor arguments ``dsn``, ``user``, ``password`` and
``options``, as well as the non-standard ``host``, ``server`` and ``db``. See
PDOK's documentation for more details.

One critical difference between ``PDO`` and ``PDOK\Connector`` is that ``PDO``
will connect to the database as soon as you instantiate it. ``PDOK\Connector``
defers creating this connection until it is actually needed.

.. _PDO: http://www.php.net/manual/en/book.pdo.php


Connection Charset
~~~~~~~~~~~~~~~~~~

If you are using MySQL and you need to set the connection's charset, you can
either use ``PDO::MYSQL_ATTR_INIT_COMMAND`` option or pass the
``connectionStatements`` key through to ``Amiss\Sql\Connector::create``.

Using ``PDO`` options:

.. code-block:: php

    <?php
    $connector = PDOK\Connector::create([
        'dsn'     => 'sqlite::memory:',
        'options' => [
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        ],
    ]);

Using ``connectionStatements``:

.. code-block:: php

    <?php
    $connector = PDOK\Connector::create([
        'dsn' => 'sqlite::memory:',
        'connectionStatements'=>[
            'SET NAMES utf8',
        ],
    ]);

