Array Mapper
============

.. note:: 

    The majority of this guide assumes you are using the :doc:`annotation` rather than the
    array mapper mentioned here. 
    
    If you have decided to use the annotation mapper, you may wish to skip this section
    and continue with the :doc:`common`.


The array mapper allows you to define your mappings as a PHP array. Fields and relations
are defined using the structure outlined in :doc:`metadata`, though some additional
conveniences are added.

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
            'primary'   => 'id',
            'fields'    => ['id' => true, 'name' => true, 'barId' => true],
            'indexes'   => ['barId' => ['fields' => 'barId']],
            'relations' => [
                'bar' => ['one', 'of' => 'Bar', 'from' => 'barId'],
            ],
        ),
   
        'Bar'=>array(
            'primary'   => 'id',
            'fields'    => ['id' => true, 'name' => true],
            'relations' => [
                'foo' => ['many', 'of' => 'Foo', 'to' => 'barId']
            ],
        ),
    );


Once your objects and mappings are defined, load load them into ``Amiss\Mapper\Arrays``
and create a manager:

.. code-block:: php

    <?php
    $mapper = new Amiss\Mapper\Arrays($mapping);
    $manager = Amiss\Sql\Factory::createManager($db, $mapper);


Mapping
-------

The mapping definitions are quite straightforward. The key to the ``$mapping`` array in
the below examples is the fully-qualified object name. Each object name is mapped to
another array containing the mapping definition.

Object mappings have the following structure:

.. code-block:: php

    <?php
    $mapping = array(
        'primary'     => ...,
        'table'       => 'table',
        'fieldType'   => null,
        'constructor' => null,
        'fields'      => [...],
        'relations'   => [...],
    );


``primary``

    The primary key can either be a single string containing the primary key's property
    name or, in the case of a composite primary key, an array listing each property name.

    The primary key does not have to appear in the field list unless you want to give it a
    specific type. If not, it will use the value of
    ``Amiss\Mapper\Arrays->defaultPrimaryType``, which defaults to ``autoinc``.


``table``

    Explicitly specify the table name the object will use.

    This value is *optional*. If it is not supplied, it will be guessed. See
    :ref:`name-translation` for more details on how this works.


``fieldType``

    All fields that do not specify a type will assume this type. See
    :doc:`types` for more details.

    This value is *optional*.


``constructor``
 
    The name of a static constructor to use when creating the object instead of the
    default ``__construct``. The method must be static and must return an instance of the
    class.

    If no constructor arguments are found in the meta, the entire unmapped input record is
    passed as the first argument.


``fields``

    An array of the object's properties that map to fields in the database table.

    The key contains the property name. The value can simply be set to ``true``, which
    indicates that no special metadata exists for the field:

    .. code-block:: php

        <?php
        $mapping = array(
            'fields' => ['name' => true, 'slug' => true, 'foo' => true, 'anotherFoo' => true],
        );

    In the above case, the column name will be guessed from the property name (see
    :ref:`name-translation`), and the type will either use the ``fieldType`` or, if
    one is not defined, no type at all.

    You can set the column and type yourself if you need to:

    .. code-block:: php
        
        <?php
        $mapping = [
            'fields' => [
                'name' => true,
                'slug' => ['type' => 'customtype'],
                'foo'  => true,
                'anotherFoo' => ['name' => 'another_foo_yippee_yay'],
            ],
        ];

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
        
        $mapping = [
            'fields' => [
                'id'   => true,
                'name' => ['getter' => 'getFoo', 'setter' => 'setFoo'],
            ],
        ];


``relations``

    A dictionary of the mapped object's relations, indexed by property name.

    Each relation value should be an array whose ``0`` element contains the name of the
    relator to use. The rest of the array should be the set of key/value pairs expected by
    the relator. See :ref:`relators` for more details on the structure of the relation
    configuration.

    .. code-block:: php
        
        <?php
        $mapping = [
            'relations' => [
                'relationProperty' => [
                    'relatorId', 'key'=>'value', 'nuddakey'=>'nuddavalue'
                ],
            ],
        ];

    Some examples of configuring the ``one`` and ``many`` relators are provided in the
    example at the top of the page.

