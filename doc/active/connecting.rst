Connecting
----------

As per the :doc:`connecting` section, create an ``Amiss\Manager``, then pass it to ``Amiss\Active\Record::setManager()``.

.. code-block:: php

    <?php
    $conn = new Amiss\Connector('sqlite::memory:');
    $amiss = new Amiss\Manager($conn);
    Amiss\Active\Record::setManager($amiss);
    
    // test it out
    $test = Amiss\Active\Record::getConnector();
    var_dump($conn === $test); // outputs true


Multiple connections are possible, but require subclasses. The separate connections are then assigned to their respective base class:

.. code-block:: php

    <?php
    abstract class Db1Record extends Amiss\Active\Record {}
    abstract class Db2Record extends Amiss\Active\Record {}
    
    class Artist extends Db1Record {}
    class Burger extends Db2Record {}
    
    Db1Record::setManager($amiss1);
    Db2Record::setManager($amiss2);
    
    // will show 'false' to prove that the record types are not 
    // sharing a connection class
    var_dump(Artist::getManager() === Burger::getManager());
