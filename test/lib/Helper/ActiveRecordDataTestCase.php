<?php
namespace Amiss\Test\Helper;

class ActiveRecordDataTestCase extends \Amiss\Test\Helper\ModelDataTestCase
{
    public function getMapper()
    {
        $mapper = parent::getMapper();
        $mapper->objectNamespace = 'Amiss\Demo\Active';
        return $mapper;
    }
}
