Connecting
==========

To use Amiss, simply create an instance of ``Amiss\Manager``, passing your connection parameters:

.. code-block:: php

    <?php
    $amiss = new Amiss\Manager(array(
        'host'=>'127.0.0.1',
        'user'=>'user', 
        'password'=>'password',
        'db'=>'amiss_demo',
    ));


Amiss will also allow a database connection object to be injected. ``Amiss\Connector`` is a PDO_-compatible object with a few enhancements. It takes the same constructor arguments, but it sets the error mode to ``PDO::ERRMODE_EXCEPTION`` by default:

.. code-block:: php

    <?php
    $manager = new Amiss\Manager(new Amiss\Connector('mysql:host=localhost;', 'user', 'password'));


You can also create an ``Amiss\Connector`` using an array of params like the initial example:

.. code-block:: php

    <?php
    $amiss = Amiss\Connector::create(array(
        'host'=>'127.0.0.1',
        'user'=>'user', 
        'password'=>'password',
    ));

Just be aware that although ``Amiss\Connector`` shares 100% of the interface with PHP 5.3's PDO_, it does not derive from it. If you're using type hints like ``function foo(\PDO $pdo)`` it won't work.

In addition to ``Amiss\Connector``, you can also pass an instance of ``PDO``, or anything else that behaves like a ``PDO`` for that matter. The advantage of ``Amiss\Connector`` is that the actual database connection is not created until a query is to be issued, whereas with ``PDO`` the connection will occur immediately. ``PDO`` compatibility is provided for convenience and simplicity.


Consider the following example:

.. code-block:: php

    <?php
    // bootstrap.php
    $amiss = new Amiss\Manager(new PDO('mysql:host=localhost;dbname=pants', $u, $p));
    
    // index.php
    require('bootstrap.php');
    echo "<html><body>Hello, welcome to my site</body></html>";
    
    // list.php
    require('bootstrap.php');
    $items = $amiss->getList('ItemList');
    foreach ($items as $i) {
        // ...
    }


When using an actual ``PDO`` as the argument to ``Amiss\Manager``, a connection to the database will be made every time someone visits the homepage even though it issues no queries. This is where ``Amiss\Connector`` makes sense:

.. code-block:: php

    <?php
    // bootstrap.php
    $amiss = new Amiss\Manager(new Amiss\Connector('mysql:host=localhost;dbname=pants', $u, $p));
    
    // index.php
    require('bootstrap.php');
    echo "<html><body>Hello, welcome to my site</body></html>";


In this example, index.php will never connect to the database. You could take it one step further and ask why we're instantiating a whole bunch of classes we don't need on the homepage, but as this is a ridiculously contrived example anyway, let's leave that one alone.


.. _PDO: http://www.php.net/manual/en/book.pdo.php

