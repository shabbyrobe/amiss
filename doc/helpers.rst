Helpers
=======

``Amiss\Manager`` has several helper methods that can take some of the pain out of complex relation gymnastics.

.. _helpers-get-children:

.. py:function:: Amiss\\Manager::getChildren( iterable $objects , $path )

    Retrieve all child values through property ``$path`` from ``$objects``.

    .. code-block:: php

        <?php
        // (object) cast creates stdClass objects
        $objects = array(
            (object)array('foo'=>'a'),
            (object)array('foo'=>'b'),
            (object)array('foo'=>'c'),
        );
        
        $children = $manager->getChildren($objects, 'foo');
        $expected = array('a', 'b', 'c');

        // this will output true
        var_dump($children == $expected);

    
    ``$path`` can be a single string containing a property name, like the above example, or it can be a path expression allowing you to traverse multiple levels:

    .. code-block:: php
        
        <?php
        $objects = array(
            (object)array('foo'=>(object)array('bar'=>'a')),
            (object)array('foo'=>(object)array('bar'=>'b')),
            (object)array('foo'=>(object)array('bar'=>'c')),
        );
        
        $children = $manager->getChildren($objects, 'foo/bar');
        $expected = array('a', 'b', 'c');

        // this will output true
        var_dump($children == $expected);

    
    ``getChildren`` will also work if the result of any path level yields an array:

    .. code-block:: php
    
        <?php
        $objects = array(
            (object)array('foo'=>array(
                (object)array('bar'=>'a'),
                (object)array('bar'=>'b'),
            ),
        );

        $children = $manager->getChildren($objects, 'foo/bar');
        $expected = array('a', 'b');

        // this will output true
        var_dump($children == $expected);

    
    ``$path`` will also accept an array:

    .. code-block:: php
    
        <?php
        $children = $manager->getChildren($objects, array('foo', 'bar'));


    See :ref:`relations-assigning-nested` for a complete example of using ``getChildren`` with :doc:`relations`.


.. py:function:: Amiss\\Manager::indexBy()

    Iterate over an array of objects and returns an array of objects indexed by a property:

    .. code-block:: php

        <?php
        $objects = array(
            (object)array('foo'=>'a'),
            (object)array('foo'=>'b'),
            (object)array('foo'=>'c'),
        );
        
        $manager = new Amiss\Manager(...);
        $indexed = $manager->indexBy($objects), 'foo';
        
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
        $indexed = $manager->indexBy($objects, 'foo', Amiss::INDEX_DUPE_FAIL);

    BZZT! ``UnexpectedValueException``!


.. py:function:: Amiss\Manager::keyValue()

    ``keyValue`` scans an array of objects or arrays and selects a property for the key and a property for the value.

    ``keyValue`` works in two ways. Firstly, you can feed it the result of a query with two columns and it'll make the first column the key and the second column the value:

    .. code-block:: php

        <?php
        $manager = new Amiss\Manager(...);
        $sql = 'SELECT artistId, name FROM artist ORDER BY artistName';
        $artists = $manager->keyValue($manager->execute($sql)->fetchAll(\PDO::FETCH_ASSOC));


    Et voila! Array of key/value pairs from your query.

    The other way is to feed it a list of objects and tell it which properties to use. This will produce the same array as the previous example (albeit way less efficiently):

    .. code-block:: php

        <?php
        $manager = new Amiss\Manager(...);
        $result = $manager->getList('Artist', array('order'=>'name'));
        $artists = $manager->keyValue($result, 'artistId', 'name'); 

