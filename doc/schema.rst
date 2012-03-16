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

There is a command line tool available in the Amiss distribution at ``bin/amiss``. The following commands will help turn a set of Active Records into a sql schema:

* bin/amiss create-tables-sql: emits sql to the command line
* bin/amiss create-tables: creates the tables in your DB

Both scripts recursively scan a directory looking for derivatives of ``Amiss\Active\Record``, and when found, will either echo ``buildCreateTableSql`` or run ``createTable``.

Both scripts will output usage information when run.

You can run commands using the demo active records from the root of the Amiss distribution like so::

    bin/amiss create-tables-sql --engine mysql doc/demo/ar.php
    bin/amiss create-tables --dsn 'sqlite:/tmp/foo.sqlite3' doc/demo/ar.php

You can also use the command line tools to emit active record classes from an existing database schema with the ``create-ars`` command.

.. warning:: I messed up - I hacked in arg parsing as I wanted to avoid the syntax errors ``getopt()`` misses, but it doesn't support arguments that use ``=``. The following will work: ``--engine mysql``, though this will not: ``--engine=mysql``. It's stupid, I know. I'll fix it at some stage.

