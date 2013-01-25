<?php
namespace Amiss\Sql;

class TypeSet extends \ArrayObject
{
    function __construct()
    {
        $this[] = array(new Type\Autoinc, array('autoinc'));
        $this[] = array(new Type\Date, array('date', 'datetime', 'timestamp'));
    }
}
