Selecting
=========

``Amiss\Active\Record`` proxies many of the methods of ``Amiss\Manager``, removing the need to specify type.

Assuming ``Artist`` is a derivative of ``Amiss\Active\Record``, the following single-object retrieval calls are equivalent:

.. code-block:: php

    <?php
    // get it directly from the Amiss\Manager
    $manager->get('Artist', 'artistId=?', 1);
    
    // get by primary key directly from the active record
    Amiss\Demo\Artist::getByPk(1);
    
    // get by custom 'where' directly from the active record
    Amiss\Demo\Artist::get('artistId=?', 1);


And the following list retrieval calls are equivalent:

.. code-block:: php

    <?php
    // get the list directly from the Amiss\Manager
    $manager->getList('Artist', 'artistTypeId=?', 1);

    // get the list from the active record
    Amiss\Demo\Artist::getList('artistTypeId=?', 1);

