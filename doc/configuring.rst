Configuring
===========

Autoloading
-----------

Amiss provides a very simple autoloader that should be compatible with well-written autoloaders from other projects:

.. code-block:: php

    <?php
    $amissPath = '/path/to/amiss';
    require_once($amissPath.'/Loader.php');
    Amiss\Loader::configure();
    spl_autoload_register(array(new Amiss\Loader, 'load'));


To use Amiss, simply create an instance of ``Amiss\Manager``, passing your connection parameters:


Manager
-------

The main class Amiss requires to do it's business is ``Amiss\Manager``. It requires a way to connect to the database and a class that can map your objects to the database and back.

The **mapper** must be an instance of ``Amiss\Mapper``. The standard mapper recommended by Amiss is ``Amiss\Mapper\Note``, which allows the use of simple annotations to declare mappings.

Creating an ``Amiss\Manager`` is simple:

.. code-block:: php

    <?php
    $db = array(
        'host'=>'127.0.0.1',
        'user'=>'user', 
        'password'=>'password',
        'db'=>'amiss_demo',
    );
    $mapper = new Amiss\Mapper\Note;
    $amiss = new Amiss\Manager($db, $mapper);


For more information on customising the mapping, please read the :doc:`mapping` section.


Database Connections
--------------------

``Amiss\Connector`` is a PDO_-compatible object with a few enhancements. It takes the same constructor arguments, but it sets the error mode to ``PDO::ERRMODE_EXCEPTION`` by default.

Just be aware that although ``Amiss\Connector`` shares 100% of the interface with PHP 5.3's PDO_, it does not derive from it. If you're using type hints like ``function foo(\PDO $pdo)`` it won't work.

One critical difference between ``PDO`` and ``Amiss\Connector`` is that ``PDO`` will *connect to the database as soon as you instantiate it*. ``Amiss\Connector`` defers creating this connection until it is actually needed.

Creating an instance of ``Amiss\Connector`` is the same as creating an instance of ``PDO``:

.. code-block:: php

    <?php
    $connector = new Amiss\Connector('mysql:host=localhost;', 'user', 'password');


You can also create an ``Amiss\Connector`` using an array of params like the initial example:

.. code-block:: php

    <?php
    $amiss = Amiss\Connector::create(array(
        'host'=>'127.0.0.1',
        'user'=>'user', 
        'password'=>'password',
    ));


``Amiss\Manager`` will also accept the same array as ``Amiss\Connector::create`` as a connection.

You can also pass ``Amiss\Manager`` an instance of ``PDO``, or anything else that behaves like a ``PDO`` for that matter, though using ``Amiss\Connector`` instead is highly recommended as some features may not work exactly as expected. 

.. warning:: ``Amiss\Connector`` is PDO_-compatible so you can use it instead of ``PDO`` in your own code, rather than so you can use a ``PDO`` with Amiss instead of an ``Amiss\Connector``.


.. _PDO: http://www.php.net/manual/en/book.pdo.php


