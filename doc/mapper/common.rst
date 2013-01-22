Common Mapper Configuration
===========================

All of the mappers provided with Amiss derive from ``Amiss\Mapper\Base``. ``Amiss\Mapper\Base``
provides some facilities for making educated guesses about what table name or property names to use
when they are not explicitly declared in your mapping configuration. It is recommended that you use
``Amiss\Mapper\Base`` when rolling your own mapper, as outlined in :doc:`custom`.


.. _name-translation:

Name translation
----------------

If your property/field mappings are not quite able to be managed by the defaults but a simple
function would do the trick (for example, you are working with a database that has no underscores in
its table names, or you have a bizarre preference for sticking ``m_`` at the start of every one of
your object properties), you can use a simple name translator to do the job for you.
``Amiss\Mapper\Base`` provides several facilities to wrangle these names without having to write a
fully custom mapper:

.. py:attribute:: Amiss\\Mapper\\Base->objectNamespace

    To save you the trouble of having to declare the full object namespace on every single call to
    ``Amiss\Sql\Manager``, you can configure an ``Amiss\Mapper\Base`` mapper to prepend any object name
    that is not `fully qualified <http://php.net/namespaces>`_ with one specific namespace by
    setting this property.

    .. code-block:: php
        
        <?php
        namespace Foo\Bar {
            class Baz {
                public $id;
            }
        }
        namespace {
            $mapper = new Your\Own\Mapper;
            $mapper->objectNamespace = 'Foo\Bar';
            $manager = new Amiss\Sql\Manager($db, $mapper);
            $baz = $manager->getById('Baz', 1);
            
            var_dump(get_class($baz)); 
            // outputs: Foo\Bar\Baz
        }


.. py:attribute:: Amiss\\Mapper\\Base->defaultTableNameTranslator
    
    Converts an object name to a table name. This property accepts either a PHP :term:`callback`
    type or an instance of ``Amiss\Name\Translator``, although in the latter case, only the ``to()``
    method will ever be used.

    If the value returned by your translator function is equal to (===) ``null``,
    ``Amiss\Mapper\Base`` will revert to the standard ``ObjectName`` to ``table_name`` method.


.. py:attribute:: Amiss\\Mapper\\Base->unnamedPropertyTranslator
    
    Converts a property name to a database column name and vice-versa. This property *only* accepts
    an instance of ``Amiss\Name\Translator``. It uses the ``to()`` method to convert a property name
    to a column name, and the ``from()`` method to convert a column name back to a property name.


.. py:attribute:: Amiss\\Mapper\\Base->convertUnknownTableNames

    If the mapper is called upon to guess a table name and the ``defaultTableNameTranslator``
    returns nothing, this determines whether the ``ObjectName`` to ``table_name`` conversion
    happens. Defaults to ``true``.


You can create your own name translator by implementing ``Amiss\\Name\\Translator`` and defining the
following methods::

    string to(string $name)
    string from(string $name)


It is helpful to name the translator based on the translation with the word "To" inbetween, i.e.
``CamelToUnderscore``.

Speaking of which, Amiss comes with the following name translators:

.. py:class:: Amiss\\Name\\CamelToUnderscore

    Translates ``ObjectName`` to ``table_name`` using the ``to()`` method, and back from
    ``table_name`` to ``ObjectName`` using the ``from()`` method.

