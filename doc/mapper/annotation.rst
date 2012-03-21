Annotation Mapper
=================

.. note:: It is assumed by this mapper that an object and a table are corresponding entities. 
    More complex mapping should be handled using a custom mapper.


To use an annotation mapper with Amiss, pass an instance of ``Amiss\Mapper\Note`` to ``Amiss\Manager``:

.. code-block:: php

    <?php
    $mapper = new \Amiss\Note\Mapper();
    $manager = new \Amiss\Manager($db, $mapper);


See :ref:`mapper-common` for more information on how to tweak the note mapper's behaviour.


Caching
-------

You really shouldn't use the annotation mapper without a cache. This mapper will slow your Amiss down significantly if you decline to provide one. You don't have to though, all it really does is a bit of reflection, but when all you have to do to get access to APC-based caching is this, there's no reason not to:

.. code-block:: php

  <?php
  $mapper = new \Amiss\Note\Mapper('apc');


If you don't want to use APC for the cache, or you're not happy with Amiss' default cache lifetime of 1 day, or you want to allow the mapper to use your own class for caching, you can pass a 2-tuple of closures. The first member should be your "get" method. It should take a single key argument and return the cached value. The second member should be your "set" method and take key and value arguments.

For example, to shove your cached metadata into the temp directory:

.. code-block:: php
    <?php
    $path = sys_get_temp_dir();
    $cache = array(
        function ($key) use ($path) {
            $key = md5($key);
            $file = $path.'/nc-'.$key;
            if (file_exists($file)) {
                return unserialize(file_get_contents($file));
            }
        },
        function ($key, $value) use ($path) {
            $key = md5($key);
            $file = $path.'/nc-'.$key;
            file_put_contents($file, serialize($value));
        }
    );
    $mapper = new \Amiss\Mapper\Note($cache);


Annotations
-----------

Annotations are javadoc-style key/values and are formatted like so:

.. code-block:: php
    
    <?php
    /**
     * @key this is the value
     */


The ``Amiss\Note\Parser`` class is used to extract these annotations. Go ahead and use it yourself if you find it useful, but keep in mind the following:

 * Everything up to the first space is considered the key. Use whatever symbols 
   you like for the key as long as it isn't whitespace.

 * The value starts after the first space after the key and ends at the first newline. 
   Currently, RFC 2822 style folding is not supported (though it may be in future if it 
   is needed by Amiss). The value is *not trimmed for whitespace*.

 * Multiple annotations per line are *not supported*.


The following class level annotations are available:

.. py:attribute:: @table value

    When declared, this forces the mapper to use this table name rather than creating a table name based on the object name.

.. py:attribute:: @fieldType value

    This sets a default field type to use for for all of the properties that do not have a field type set against them explicitly. This will inherit from a parent class if one is set.
