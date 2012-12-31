Array Mapper
============

.. note:: 

    The remainder of the guide assumes you are using the :doc:`annotation` rather than the array
    mapper mentioned here. This mapper is provided as an alternative. If you have decided to use the
    annotation mapper, you may wish to skip this section and continue with the :doc:`common`.


The array mapper allows you to define your mappings as a PHP array. Fields and relations are defined
using the structure outlined in :doc:`metadata`, though some additional conveniences are added.

Mapping your objects is quite simple:

.. code-block:: php

    <?php
    class Foo
    {
        public $id;
        public $name;
        public $barId;

        public $bar;
    }

    class Bar
    {
        public $id;
        public $name;

        public $child;
    }

    $mapping = array(
        'Foo'=>array(
            'primary'=>'id',
            'fields'=>array('id', 'name', 'barId'),
            'relations'=>array(
                'bar'=>array('one', 'of'=>'Bar', 'on'=>'barId'),
            ),
        ),

        'Bar'=>array(
            'primary'=>'id',
            'fields'=>array('id', 'name'),
            'relations'=>array(
                'foo'=>array('many', 'of'=>'Foo')
            ),
        ),
    );



Once your objects and mappings are defined, load load them into ``Amiss\Mapper\Arrays`` and assign
it to ``Amiss\Manager``:

.. code-block:: php

    <?php
    $mapper = new Amiss\Mapper\Arrays($mapping);
    $manager = new Amiss\Manager($db, $mapper);


Mapping
-------

The mapping definitions are quite straightforward. The key to the ``$mapping`` array in the below
examples is the fully-qualified object name. Each object name is mapped to another array containing
the mapping definition.


Object mappings have the following structure:

.. code-block:: php

    <?php
    $mapping = array(
        'primary'=>...,
        'table'=>'table',
        'defaultFieldType'=>null,
        'fields'=>array(...),
        'relations'=>array(...),
    );


.. py:attribute:: primary

    The primary key can either be a single string containing the primary key's property name or, in
    the case of a composite primary key, an array listing each property name.

    The primary key does not have to appear in the field list unless you want to give it a specific
    type. If not, it will use the value of ``Amiss\Mapper\Arrays->defaultPrimaryType``, which
    defaults to ``autoinc``.


.. py:attribute:: table

    Explicitly specify the table name the object will use.

    This value is *optional*. If it is not supplied, it will be guessed. See :ref:`name-translation`
    for more details on how this works.


.. py:attribute:: defaultFieldType

    All fields that do not specify a type will assume this type. See :doc:`types` for more
    details.

    This value is *optional*.


.. py:attribute:: fields

    An array of the object's properties that map to fields in the database table.

    This can be a simple list of strings containing the property names:

    .. code-block:: php

        <?php
        $mapping = array(
            'fields'=>array('name', 'slug', 'foo', 'anotherFoo'),
        );

    In this case, the column name will be guessed from the property name (see 
    :ref:`name-translation`), and the type will either use the ``defaultFieldType`` or, if one is 
    not defined, no type at all.

    You can set the column and type yourself if you need to:

    .. code-block:: php
        
        <?php
        $mapping = array(
            'fields'=>array(
                'name',
                'slug'=>array('type'=>'customtype'),
                'foo',
                'anotherFoo'=>array('name'=>'another_foo_yippee_yay'),
            ),
        );
    
    Properties that use getters and setters can also be mapped:

    .. code-block:: php

        <?php
        class Foo
        {
            public $id;
            private $foo;

            public function getFoo()   { return $this->foo; }
            public function setFoo($v) { $this->foo = $v; }
        }
        
        $mapping = array(
            'fields'=>array(
                'id',
                'name'=>array('getter'=>'getFoo', 'setter'=>'setFoo'),
            ),
        );


.. py:attribute:: relations

    A dictionary of the mapped object's relations, indexed by property name.

    Each relation value should be an array whose ``0`` element contains the name of the relator to
    use. The rest of the array should be the set of key/value pairs expected by the relator. See
    :ref:`relators` for more details on the structure of the relation configuration.

    .. code-block:: php
        
        <?php
        $mapping = array(
            'relations'=>array(
                'relationProperty'=>array('relatorId', 'key'=>'value', 'nuddakey'=>'nuddavalue'),
            ),
        );

    Some examples of configuring the ``one`` and ``many`` relators are provided in the example at
    the top of the page.

