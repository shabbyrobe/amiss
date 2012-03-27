.. _mapper-common:

Common Mapper Configuration
===========================

``Amiss\Mapper\Note`` derives from ``Amiss\Mapper\Base``. ``Amiss\Mapper\Base`` provides some facilities for making educated guesses about what table name or property names to use when they are not explicitly declared in your mapping configuration.

Anything that derives from ``Amiss\Mapper\Base`` can inherit this functionality. It is recommended that you use ``Amiss\Mapper\Base`` when rolling your own mapper, as outlined in :doc:`custom`.


.. _name-translation:

Name translation
^^^^^^^^^^^^^^^^

If your property/field mappings are not quite able to be managed by the defaults but a simple function would do the trick (for example, you are working with a database that has no underscores in its table names, or you have a bizarre preference for sticking ``m_`` at the start of every one of your object properties), you can use a simple name translator to do the job for you using the following properties:


.. py:attribute:: Amiss\\Mapper\\Base->objectNamespace

    To save you the trouble of having to declare the full object namespace on every single call to ``Amiss\Manager``, you can configure an ``Amiss\Mapper\Base`` mapper to prepend any object name that is not `fully qualified <http://php.net/namespaces>`_ with one specific namespace by setting this property.

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
            $manager = new Amiss\Manager($db, $mapper);
            $baz = $manager->getByPk('Baz', 1);
            
            var_dump(get_class($baz)); 
            // outputs: Foo\Bar\Baz
        }


.. py:attribute:: Amiss\\Mapper\\Base->defaultTableNameTranslator
    
    Converts an object name to a table name. This property accepts either a PHP :term:`callback` type or an instance of ``Amiss\Name\Translator``, although in the latter case, only the ``to()`` method will ever be used.

    If the value returned by your translator function is equal to (===) ``null``, ``Amiss\Mapper\Base`` will revert to the standard ``TableName`` to ``table_name`` method.


.. py:attribute:: Amiss\\Manager\\Base->unnamedPropertyTranslator
    
    Converts a property name to a database column name and vice-versa. This property *only* accepts an instance of ``Amiss\Name\Translator``. It uses the ``to()`` method to convert a property name to a column name, and the ``from()`` method to convert a column name back to a property name.


You can create your own name translator by implementing ``Amiss\\Name\\Translator`` and defining the following methods::

    string to(string $name)
    string from(string $name)


It is helpful to name the translator based on the translation with the word "To" inbetween, i.e. ``CamelToUnderscore``.

Speaking of which, Amiss comes with the following name translators:

.. py:class:: Amiss\\Name\\CamelToUnderscore

    Translates ``TableName`` to ``table_name`` using the ``to()`` method, and back from ``table_name`` to ``TableName`` using the ``from()`` method.


.. _type-handling:

Type Handling
^^^^^^^^^^^^^

There's very little intelligence in how Amiss handles values coming in and out of the database. They go in and out of the DB as whatever PDO treats them as by default, which is pretty much always strings or nulls.

This may be fine for 98% of your interaction with the database (trust me - it really will be), but then along come dates and throw a whopping big spanner in the works.

How are you persisting dates? Probably as a YYYY-MM-DD formatted string, yeah? Maybe as a unix timestamp. What about the occasional serialised object?

``Amiss\Mapper\Base`` provides a facility for handling specific database types arbirtrarily.


Using Type Handlers
^^^^^^^^^^^^^^^^^^^

Amiss provides the following type handlers out of the box:

.. py:class:: Amiss\Type\Date($withTime=true, $timeZone=null)

    Converts database ``DATE`` or ``DATETIME`` into a PHP ``DateTime`` on object creation and PHP DateTime objects into a ``DATE`` or ``DATETIME`` on row export.

    :param withTime: Pass ``true`` if the type is a ``DATETIME``, ``false`` if it's a ``DATE``
    :param timeZone: Use this timezone with all created ``DateTime`` objects. If not passed, will rely on PHP's default timezone (see `date_default_timezone_set <http://php.net/date_default_timezone_set>`_)


In order to register this handler with Amiss and allow it to be used, you need to either assign it directly by key to the ``Amiss\Mapper\Base->typeHandlers`` array, or if registering the same handler to many types, using ``Amiss\Mapper\Base::addTypeHandler($typeHandler(s), $id)``:

.. code-block:: php

    <?php
    // anything which derives from Amiss\Mapper\Base will work.
    $mapper = new Amiss\Mapper\Note;
    $dateHandler = new Amiss\Type\Date;
    $mapper->addTypeHandler($dateHandler, array('datetime', 'timestamp'));


.. note:: Type handler IDs are always lower case, even if the field type contains uppercase letters

