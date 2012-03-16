Annotation Mapper
=================

.. note:: It is assumed by this mapper that an object and a table are corresponding entities. 
    More complex mapping should be handled using a custom mapper.


To use an annotation mapper with Amiss, pass an instance of ``Amiss\Mapper\Note`` to ``Amiss\Manager``:

.. code-block:: php

    <?php
    $mapper = new \Amiss\Note\Mapper;
    $manager = new \Amiss\Manager($db, $mapper);


See :ref:`mapper-common` for more information on how to tweak the note mapper's behaviour.


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


The following class level annotations are available:

.. py:attribute:: @table value

    When declared, this forces the mapper to use this table name rather than creating a table name based on the object name.

.. py:attribute:: @fieldType value

    This sets a default field type for all 
