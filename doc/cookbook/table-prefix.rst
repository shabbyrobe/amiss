Table Prefixes
==============

If you want to rely on Amiss' default ``ObjectName`` to ``table_name`` conversion but you want each of your table names to be automatically prefixed, you can wrap the ``Amiss\Name\CamelToUnderscore`` translator and pass it to ``defaultTableNameTranslator``.

This requires your chosen mapper to derive from ``Amiss\Mapper\Base``, such as ``Amiss\Mapper\Note`` or ``Amiss\Mapper\Arrays``. See :doc:`/mapper/common` for more details.

.. code-block:: php

    <?php
    $mapper = new \Amiss\Mapper\Note();
    
    $tablePrefix = 'yep_';
    $translator = new Amiss\Name\CamelToUnderscore();

    $mapper->defaultTableNameTranslator = function($objectName) use ($translator) {
        return 'yep_'.$translator->translate($objectName);
    };
    
    $manager = new \Amiss\Sql\Manager($db, $mapper);
