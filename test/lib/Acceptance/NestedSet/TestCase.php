<?php
namespace Amiss\Test\Acceptance\NestedSet;

abstract class TestCase extends \Amiss\Test\Helper\CustomMapperTestCase
{
    protected function createNestedSetArrayManager($map)
    {
        $mapper = new \Amiss\Mapper\Arrays($map);
        $config = [
            'dbTimeZone'=>'UTC',
        ];
        $mapper->typeHandlers = \Amiss\Sql\Factory::createTypeHandlers($config);
        $manager = $this->createNestedSetManager($mapper, array_keys($map));
        return $manager;
    }

    public function createNestedSetNoteManager($classes)
    {
        $ns = null;
        $classNames = [];
        if ($classes) {
            list ($classHash, $ns, $classNames) = $this->createClasses($classes);
        }
        $nsManager = $this->createNestedSetManager(null, $classNames);
        if ($ns) {
            $nsManager->manager->mapper->objectNamespace = $ns;
        }
        $this->prepareManager($nsManager->manager, $classNames);
        return [$nsManager, $ns];
    }

    protected function createNestedSetNoteMapper($map)
    {
        $mapper = new \Amiss\Mapper\Arrays($map);
        $config = [
            'dbTimeZone'=>'UTC',
        ];
        $mapper->typeHandlers = \Amiss\Sql\Factory::createTypeHandlers($config);
        $manager = $this->createNestedSetManager($mapper, array_keys($map));
        return $manager;
    }

    protected function createNestedSetManager($mapper, $classNames)
    {
        $connector = $this->getConnector();
        $info = $this->getConnectionInfo();
        $manager = $this->createDefaultManager($mapper);
        $manager = \Amiss\Ext\NestedSet\Manager::createWithDefaults($manager);
        return $manager;
    }

    function idTree($parent, $tree, $idProp='id', $treeProp='tree')
    {
        $r = function($c) use (&$r, $idProp, $treeProp) {
            if (!$c) { return true; }
            $ids = [];
            foreach ($c as $child) {
                $ids[$child->$idProp] = isset($child->$treeProp) ? $r($child->$treeProp) : true;
            }
            return $ids;
        };
        return [$parent->$idProp=>$r($tree)];
    }
}

