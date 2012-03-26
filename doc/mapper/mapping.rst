Mapping
=======

Amiss stores database mapping information for your objects using the ``Amiss\Meta`` class. Amiss provides several complete options for retrieving this metadata as well as facilities for rolling your own mapper if you prefer.

.. toctree::
    :maxdepth: 2

    annotation
    array
    common
    metadata
    custom


The annotation mapper is used throughout this documentation.


Annotations Quickstart
----------------------

Amiss provides a javadoc-style key/value mapper called ``Amiss\Mapper\Note``, which derives from ``Amiss\Mapper\Base``. 

See :doc:`annotation` for more details.

Objects are marked up in this way:

.. code-block:: php

    <?php
    /**
     * @table your_table
     * @fieldType VARCHAR(255)
     */
    class Foo
    {
        /** @primary */
        public $id;

        /** @field some_column */
        public $name;

        /** @field */
        public $barId;

        /** 
         * One-to-many relation:
         * @has many of=Bar 
         */
        public $bars;

        /**
         * One-to-one relation: 
         * @has one of=Baz; on=bazId
         */
        public $baz;

        // field is defined below using getter/setter
        private $fooDate;

        /**
         * @field
         * @type date
         */
        public function getFooDate()
        {
            return $this->fooDate;
        }

        public function setFooDate($value)
        {
            $this->fooDate = $value;
        }
    }
