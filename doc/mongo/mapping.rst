Mapping
=======

Mongo mapping is derived entirely from the mapping methods described in the :doc:`Amiss core
mapping documentation </mapper/mapping>`. Please read this documentation thoroughly and select your
preferred mapping method before reading this document.


Mapper Setup
------------

Amiss provides the ``Amiss\Mongo\TypeSet`` that offers support for Mongo's dates, ids and embedded 
documents. Once you have configured your chosen mapper (assumed to be ``Amiss\Mapper\Note`` 
throughout this documentation), pass the TypeSet to the ``addTypeSet()`` method:

.. code-block:: php

    <?php
    $mapper = new \Amiss\Mapper\Note();
    $mapper->objectNamespace = 'Amiss\Demo';
    $mapper->addTypeSet(new \Amiss\Mongo\TypeSet);


Mongo Types
-----------

Mapping a Mongo ID:

.. code-block:: php

    <?php
    class Event
    {
        /**
         * @field _id
         * @type id
         */
        public $id;
    }

This will convert an instance of ``MongoID`` to a string value on retrieval.


Embedded Document Support
-------------------------

The Mongo ``TypeSet`` provides a preconfigured ``@type`` named ``embed``. This makes use of the
:ref:`embed` type handler.
