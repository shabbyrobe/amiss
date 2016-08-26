Choosing a Mapper
=================

``Amiss\Mapper\Note``
    Allows annotations to be used to declare mapping metadata using the "`Nope
    <http://github.com/shabbyrobe/nope>`_" library. See :doc:`annotation` for
    details.

    This mapper is used throughout the documentation.

    The advantage of the note mapper is that metadata is always declared
    adjacent to the class definitions, and some information can be inferred from
    the class.

    The disadvantage is that it involves an extra parsing step because it needs
    to extract the metadata from docblocks, but this can be largely mitigated
    with a :ref:`cache <note-caching>`.

    .. code-block:: php
        
        <?php
        /** :amiss = {"table": "foo"}; */
        class Foo {
            /** :amiss = {"field": true}; */
            public $yep;
        }

``Amiss\Mapper\Arrays``
    Metadata is declared in a whopping big configuration array. See :doc:`array`
    for details.

    The fastest way to handle your mappings by a street, but it requires you to
    basically retype your entire class definition outside your class' file.

    .. code-block:: php

        <?php
        class Foo {
            public $yep;
        }
        $mapper = new Amiss\Mapper\Arrays([
            'Foo' => [
                'table'  => 'foo',
                'fields' => ['yep' => true],
            ]
        ]);

``Amiss\Mapper\Local``
    This is very similar to the :doc:`array mapper <array>`, but allows you to
    define a class' mappings inside the class itself:

    .. code-block:: php

        <?php
        class Foo {
            public $yep;
            public static function meta() {
                return [
                    'table'  => 'foo',
                    'fields' => ['yep' => true],
                ];
            }
        }
        $mapper = new Amiss\Mapper\Local();

``Amiss\Mapper\Chain``
    Allows a group of mappers to be called in order until one resolves.

    .. warning:: This mapper caches lookups internally even when an
       ``Amiss\Cache`` is not used. If this is not what you want, set
       ``Amiss\Mapper\Chain->useInternalCache`` to ``false``.

    .. code-block:: php
        
        <?php
        $mappers = [
            new Amiss\Mapper\Note(...),
            new Amiss\Mapper\Local(),
        ];
        $mapper = new Amiss\Mapper\Chain($mappers);
        $mapper = new Amiss\Mapper\Chain($mappers, $cache);

