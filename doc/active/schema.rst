Table Creation
==============

If you're feeling really, really lazy, you can let ``Amiss\Active\Record`` create your tables for you too. All you have to do is provide an array of the fields:

.. code-block:: php

    <?php
    namespace Amiss\Demo;
    class Artist extends \Amiss\Active\Record
    {
        public static $fields = array(
            'name', 'slug'
        );
        
        public $slug;
    }


You can omit the property declarations if you like; notice there is no property for ``name``. It will still be set just fine if you retrieve an ``Artist`` from the database. The primary key is also omitted in the above example. It will be inferred when the table is created if you do not specifically set the static ``$primary`` property.

Once you have declared the fields, you can then either tell the ``Active\Record`` to create its own table, or emit the SQL for you to use as you please:

.. code-block:: php

    <?php
    $sql = Artist::buildCreateTableSql();
    Artist::createTable();

See the ``Field Mapping`` section of :doc:`defining` for details on how Amiss knows what types to use for fields.


Crappy Command Line Tools
~~~~~~~~~~~~~~~~~~~~~~~~~

There are some  scripts in the ``/bin`` directory of the Amiss distribution that help when working with schema creation. They don't (and probably won't) do migrations, just the initial setup.

* ar-create-sql.php: emits sql to the command line
* ar-create.php: creates the tables in your DB

Both scripts recursively scan a directory looking for derivatives of ``Amiss\Active\Record``, and when found, will either echo ``buildCreateTableSql`` or run ``createTable``.

Both scripts will output usage information when run.

You can run both commands using the demo active records like so::

    php ar-create-sql.php --engine mysql ../doc/demo/ar.php
    php ar-create.php --dsn 'sqlite:/tmp/foo.sqlite3' ../doc/demo/ar.php


.. warning:: I messed up - I hacked in arg parsing as I wanted to avoid the syntax errors ``getopt()`` misses, but it doesn't support arguments that use ``=``. The following will work: ``--engine mysql``, though this will not: ``--engine=mysql``. It's stupid, I know. I'll fix it at some stage.

