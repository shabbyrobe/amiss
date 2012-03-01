Mapping
=======

Translation between object/table names and property/field names can be handled using Amiss' default set of opinions, or it can be completely customised.

It is assumed that an object and a table are corresponding entities. More complex mapping is outside the scope of what Amiss intends to provide.


Default Mapping Method
----------------------

Amiss does not require that you specify any table or property/field mappings. It does not introspect your database's schema and it does not check your class for annotations. In order to determine what table and field names to use when querying, it has to guess.


Table Mapping
~~~~~~~~~~~~~

Words in the object's name are ``PascalCased``. Words in the table's name will be ``underscore_separated``. Amiss will handle this translation.

This default can be disabled using the following property:

.. py:attribute:: Amiss\\Manager->convertTableNames
    
    Defaults to ``true``. Set to ``false`` if your table's names will be exactly the same as your objects.


At the moment, it is only possible for an instance of ``Amiss\Manager`` to manage one model namespace, unless you want to write the full namespace for every operation:

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    class Foo { public $id; }

    $manager = ...;
    $manager->get('Amiss\Demo\Foo', 1);
    $manager->objectNamespace = 'Amiss\Demo';
    $manager->get('Foo', 1);


Property Mapping
~~~~~~~~~~~~~~~~

When relying on the default mapping, the object's fields have the same name as the table's columns.

The following property can help tweak this behaviour:

.. py:attribute:: Amiss\\Manager->convertFieldUnderscores

    Defaults to ``false``. Set to ``true`` if your table's field names are ``underscore_separated``, but your object's properties are ``camelCased``


When selecting an object, Amiss will simply assume that each field has a correspondingly named property in the object (taking into account the underscores issue mentioned above if enabled).

Inserting and updating by object will enumerate all publicly accessible properties of the object that **aren't an array, an object or null** and *assume they are a column to be saved*:

.. code-block:: php

    <?php
    class FooBar
    {
        // explicit scalar value will be assumed to be a column
        public $yep1='yep';

        // same as above
        public $yep2=2;

        // false !== null, so this is considered a column value
        public $yep3=false;

        // public properties are null by default, so this is skipped
        public $nope1;

        // let's put an array in here later. it won't be considered.
        public $nope2;

        // let's put an object in here later. it won't be considered.
        public $nope3;

        // explicitly null public property, not considered a column
        public $nope4=null;

        // protected properties are not accessible to a foreach loop over an object, 
        // so it is not considered a column value
        protected $nope3='nope';

        // see protected property
        private $nope4='nope';
    }

    $fb = new FooBar;
    $fb->nope2 = array('a', 'b');
    $fb->nope3 = new stdClass;
    $manager->insert($fb);

    // will generate the following statement:
    // INSERT INTO foo_bar(yep1, yep2, yep3) VALUES(:yep1, :yep2, :yep3)


The rationale for this is as follows:

* Objects are skipped because they are assumed to belong to relations, and should be saved separately
* Arrays have no 1 to 1 representation in MySQL that isn't platform agnostic, and are also likely to represent 1-to-n relations (as in ``Event->eventArtists``)
* An object with a property representing a relation will have a null value if there is no related object, but there will be no field in the database. 

.. warning:: There is a potentially serious gotcha documented here: :ref:`null-handling`


Custom Mapping
--------------

In spite of the :ref:`null-handling`, the default behaviour will work well in quite a lot of situations. 

In the event that it doesn't, there are options:


Name Mappers
~~~~~~~~~~~~

If your object/table or property/field mappings are not quite able to be managed by the defaults but a simple function would do the trick (for example, you are working with a database that has no underscores in its table names, or you have a bizarre preference for sticking ``m_`` at the start of every one of your object properties), you can use a simple name mapper to do the job for you using the following properties:

.. py:attribute:: Amiss\\Manager->objectToTableMapper
    
    Converts an object name to a table name. This property accepts either a PHP :term:`callback` type or an instance of ``Amiss\Name\Mapper``, although in the latter case, only the ``to()`` method will ever be used.


.. py:attribute:: Amiss\\Manager->propertyColumnMapper
    
    Converts a property name to a database column name and vice-versa. This property *only* accepts an instance of ``Amiss\Name\Mapper``. It uses the ``to()`` method to convert a property name to a column name, and the ``from()`` method to convert a column name back to a property name.



Bugger This, I'll Do It Myself!
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Amiss allows you to manually specify table names for objects. The object name **must** contain the namespace.

.. code-block:: php

    <?php
    $manager = new Amiss\Manager(...);
    $manager->tableMap['My\Object'] = 'some_weirdo_TNAME';


Amiss provides two interfaces for custom property/field mapping:

.. py:class:: interface Amiss\\RowExporter

    .. py:method:: exportRow()

    Handles converting an object's properties into an array that represents the row. Array keys should *exactly* match the field names.

.. py:class:: interface Amiss\\RowBuilder

    .. py:method:: buildObject(array $row)

    Handles assigning the row's values to the object's properties.


.. code-block:: php

    <?php
    class FooBar implements Amiss\RowExporter, Amiss\RowBuilder
    {
        public $name;
        public $anObject;
        public $setNull;
        
        public function exportRow()
        {
            $values = (array)$this;
            $values['anObject'] = serialize($values['anObject']);
            return $values;
        }

        public function buildObject(array $row)
        {
            $this->name = $row['name'];
            $this->anObject = unserialize($row['anObject']);
            $this->setNull = $row['setNull'];
        }
    }
    $fb = new FooBar();
    $fb->anObject = new stdClass;
    $manager->insert($fb);


In the above example, ``exportRow()`` will be called by ``Amiss\Manager`` in order to get the values to use in the ``INSERT`` query, completely bypassing the default row export.

I can hear you screaming: "Get your damn hands off my model". I agree. But it could be worse for a domain-model purist: it could be one of those pesky :doc:`/active/index`, rather than a relatively unobtrusive interface. Besides, such purism would be far better served by `Doctrine <http://www.doctrine-project.org/>`_.

