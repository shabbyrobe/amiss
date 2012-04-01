Configuring
===========

Autoloading
-----------

Amiss provides a very simple autoloader that should be compatible with well-written autoloaders from other projects:

.. code-block:: php

    <?php
    $amissPath = '/path/to/amiss';
    require_once($amissPath.'/Loader.php');
    Amiss\Loader::register();


Amiss is :term:`PSR-0` compliant, so you can use any loader that supports that standard.


Manager
-------

The main class Amiss requires to do its business is ``Amiss\Manager``. It requires a way to connect to the database and a class that can map your objects to the database and back.

The **mapper** must implement the ``Amiss\Mapper`` interface. The standard mapper recommended by Amiss is ``Amiss\Mapper\Note``, which allows the use of simple annotations to declare mappings.

.. warning:: Amiss is built to support MySQL and SQLite **only**. It may work with other ANSI-compliant RDBMSssseseses, but it is not tested or supported.


Creating an ``Amiss\Manager`` is simple:

.. code-block:: php

    <?php
    $db = array(
        'dsn'=>'mysql:host=localhost;dbname=amiss_demo',
        'user'=>'user', 
        'password'=>'password',
    );
    $mapper = new Amiss\Mapper\Note;
    $manager = new Amiss\Manager($db, $mapper);


For more information on customising the mapping, please read the :doc:`mapper/mapping` section.


Database Connections
--------------------

In addition to the array shown above, ``Amiss\Manager`` can also be passed an ``Amiss\Connector`` object. ``Amiss\Connector`` is a PDO_-compatible object with a few enhancements. It takes the same constructor arguments, but it sets the error mode to ``PDO::ERRMODE_EXCEPTION`` by default.

Creating an instance of ``Amiss\Connector`` is the same as creating an instance of ``PDO``:

.. code-block:: php

    <?php
    $connector = new Amiss\Connector('mysql:host=localhost;', 'user', 'password');


You can also create an ``Amiss\Connector`` using an array containing the connection details:

.. code-block:: php

    <?php
    $connector = Amiss\Connector::create(array(
        'dsn'=>'mysql:host=localhost;dbname=amiss_demo',
        'user'=>'user', 
        'password'=>'password',
    ));

``create()`` is quite tolerant in what it accepts. You can pass it names that correspond to PDO's constructor arguments ``dsn``, ``user``, ``password`` and ``options``, as well as the non-standard ``host``, ``server`` and ``db``... it'll even assume anything that starts with a ``u`` or a ``p`` corresponds to ``user`` and ``password`` respectively.

``Amiss\Manager`` will also accept the same array as ``Amiss\Connector::create`` as a connection.

.. note:: 

    You *can* pass ``Amiss\Manager`` an instance of ``PDO``, or anything else that behaves like a ``PDO`` for that matter, though using ``Amiss\Connector`` instead is highly recommended as some features may not work exactly as expected.

    ``Amiss\Connector`` is PDO_-compatible so you can use it instead of ``PDO`` in your own code, rather than so you can use a ``PDO`` with Amiss instead of an ``Amiss\Connector``.

    Just be aware that although ``Amiss\Connector`` shares 100% of the interface with PHP 5.3's PDO_, it does not derive from it. If you're using type hints like ``function foo(\PDO $pdo)`` it won't work.

    One critical difference between ``PDO`` and ``Amiss\Connector`` is that ``PDO`` will *connect to the database as soon as you instantiate it*. ``Amiss\Connector`` defers creating this connection until it is actually needed.


.. _PDO: http://www.php.net/manual/en/book.pdo.php


Connection Charset
~~~~~~~~~~~~~~~~~~

If you are using MySQL and you need to set the connection's charset, you can either use ``PDO::MYSQL_ATTR_INIT_COMMAND`` option or pass the ``connectionStatements`` key through to ``Amiss\Connector::create``.

Using ``PDO`` options:

.. code-block:: php

    <?php
    $connector = Amiss\Connector::create(array(
        'dsn'=>...,
        'options'=>array(
            \PDO::MYSQL_ATTR_INIT_COMMAND=>'SET NAMES utf8',
        ),
    ));

Using ``connectionStatements``:

.. code-block:: php

    <?php
    $connector = Amiss\Connector::create(array(
        'dsn'=>...,
        'connectionStatements'=>array(
            'SET NAMES utf8',
        ),
    ));
