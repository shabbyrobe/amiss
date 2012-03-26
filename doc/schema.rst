Table Creation
==============

If you're feeling really, really lazy, you can use ``Amiss\TableBuilder`` to create your tables for you too. This is a *very limited tool* and is not intended to do anything other than spit out some fairly generic initial tables.

Once you have declared your object, you can then either tell the ``Amiss\TableBuilder`` to create the table in the database directly, or emit the SQL for you to use as you please:

.. code-block:: php

    <?php
    class Artist
    {
        /** @primary */
        public $artistId;

        /** @field */
        public $name;
    }

    $builder = new Amiss\TableBuilder($manager, 'Artist');
    $sql = $builder->buildCreateTableSql();
    $builder->createTable();

See the ``Field Mapping`` section of :doc:`mapper/mapping` for details on how Amiss knows what types to use for fields.


Crappy Command Line Tools
~~~~~~~~~~~~~~~~~~~~~~~~~

There is a crappy command line tool available in the Amiss distribution at ``bin/amiss``. The following commands will help turn a set of classes into a sql schema:

* bin/amiss create-tables-sql: emits sql to the command line
* bin/amiss create-tables: creates the tables in your DB

Both scripts recursively scan a directory looking for classes that match the criteria you specify, and when found, will either echo ``buildCreateTableSql`` or run ``createTable``.

Both scripts will output usage information when run with no arguments.

You can filter the classes by namespace or by annotation for both of these commands. Searching by annotation will allow you to include only classes that have the class level annotations you specify. For example, you can set your classes up like so:

.. code-block:: php

    <?php
    /** @foobar */
    class Thingy {}

    /** @bazqux */
    class OtherThingy {}

    /** @dingdong */
    class NopeThingy {}

And then pass the following arguments to either command ``--note foobar --note bazqux``, and only the ``Thingy`` and ``OtherThingy`` class will be used.

You can run commands using the demo from the root of the Amiss distribution like so::

    bin/amiss create-tables-sql --engine mysql --namespace Amiss\\Demo doc/demo/model.php
    bin/amiss create-tables --dsn 'sqlite:/tmp/foo.sqlite3' --namespace Amiss\\Demo\\Active doc/demo/ar.php

You can also use the command line tools to emit active record classes from an existing database schema with the ``create-ars`` command.

.. warning:: I messed up - I hacked in arg parsing as I wanted to avoid the syntax errors ``getopt()`` misses, but it doesn't support arguments that use ``=``. The following will work: ``--engine mysql``, though this will not: ``--engine=mysql``. It's stupid, I know. I'll fix it at some stage.

