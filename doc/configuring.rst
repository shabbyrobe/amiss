Configuring
===========

Amiss provides a very simple autoloader that should be compatible with well-written autoloaders from other projects:

.. code-block:: php

    <?php
    $amissPath = '/path/to/amiss';
    require_once($amissPath.'/Loader.php');
    spl_autoload_register(array(new Amiss\Loader, 'load'));
