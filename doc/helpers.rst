Helpers
=======

``Amiss\Manager`` has several helper methods that can take some of the pain out of complex relation gymnastics.


The ``indexBy()`` method
------------------------

``indexBy()`` iterates over an array of objects and returns an array of objects indexed by a property:

.. code-block:: php

    <?php
    $objects = array(
        (object)array('foo'=>'a'),
        (object)array('foo'=>'b'),
        (object)array('foo'=>'c'),
    );
    
    $manager = new Amiss\Manager(...);
    $indexed = $manager->indexBy('foo', $objects);
    
    // this will output array('a', 'b', 'c')
    var_dump(array_keys($indexed));
    
    // this will output true
    var_dump($objects[0] == $indexed['a']); // will output true


If you have more than one object with the same property value, ``indexBy`` will merrily overwrite an existing key. Pass ``Amiss::INDEX_DUPE_FAIL`` as the third parameter if you would prefer an exception on a duplicate key:

.. code-block:: php

    <?php
    $objects = array(
        (object)array('foo'=>'a'),
        (object)array('foo'=>'a'),
        (object)array('foo'=>'b'),
    );
    $manager = new Amiss\Manager(...);
    $indexed = $manager->indexBy('foo', $objects, Amiss::INDEX_DUPE_FAIL);

BZZT! ``UnexpectedValueException``!


The ``keyValue()`` method
-------------------------

``keyValue`` scans an array of objects or arrays and selects a property for the key and a property for the value.

``keyValue`` works in two ways. Firstly, you can feed it the result of a query with two columns and it'll make the first column the key and the second column the value:

.. code-block:: php

    <?php
    $manager = new Amiss\Manager(...);
    $artists = $manager->keyValue($manager->execute('SELECT artistId, name FROM artist ORDER BY artistName')->fetchAll(\PDO::FETCH_ASSOC));


Et voila! Array of key/value pairs from your query.

The other way is to feed it a list of objects and tell it which properties to use. This will produce the same array as the previous example (albeit way less efficiently):

.. code-block:: php

    <?php
    $manager = new Amiss\Manager(...);
    $artists = $manager->keyValue($manager->getList('Artist', array('order'=>'name')), 'artistId', 'name'); 

