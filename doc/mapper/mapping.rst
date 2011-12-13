Mapping
=======

Translating object names to table names, property names to field names, this to that, etc, is governed by a few simple properties.

Object name to table name:

.. code-block:: php

    <?php
    $amiss->objectToTableMapper = function($object) {
        return "table_for_".strtolower($object);
    };

http://dev.mysql.com/doc/refman/5.0/en/identifier-case-sensitivity.html


Converting property names to column names
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Property names are mapped to columns by inserting an underscore before each capital letter then converting to lower case, i.e. ``artistName`` becomes ``artist_name``. This behaviour can be changed by assigning a a `callback <http://php.net/manual/en/language.pseudo-types.php>`_ to the ``Amiss\Manager->propertyColumnMapper`` property:

.. code-block:: php

    <?php
    // all field names are just lower cased versions of properties,
    // i.e. artistName becomes artistname:
    $amiss = new Amiss\Manager(...);
    $amiss->propertyColumnMapper = function($propertyName) {
        return strtolower($propertyName);
    };


Namespaces
~~~~~~~~~~

At the moment, it is only possible for an instance of ``Amiss\Manager`` to manage one model namespace, unless you want to write the full namespace for every operation:

.. code-block:: php

    <?php
    $amiss->objectNamespace = 'Amiss\Demo';

    // $amiss->tableMap['ft\lib\model\wp\WpPost'] = 'wp_posts';
