<?php
namespace Amiss\Test\Helper;

class CustomMapperTestCase extends \Amiss\Test\Helper\DataTestCase
{
    private static $classes = [];
    private $tearDownStatements = [];

    protected function createClasses($classes)
    {
        $classes = (array)$classes;

        $hash = '';
        foreach ($classes as $k=>$v) {
            $hash .= "$k|$v|";
        }
        $classHash = sha1($hash);
        if (isset(self::$classes[$classHash])) {
            list ($ns, $classes) = self::$classes[$classHash];
        }
        else {
            $ns = "AmissTest_".$classHash;
            $script = "namespace $ns;";
            foreach ($classes as $k=>$v) {
                $script .= $v;
            }
            $classes = get_declared_classes();
            eval($script);
            $classes = array_values(array_diff(get_declared_classes(), $classes));
            self::$classes[$classHash] = [$ns, $classes];
        }
        return [$classHash, $ns, $classes];
    }

    public function createDefaultNoteManager($classes, $connector=null)
    {
        $ns = null;
        $classNames = [];
        if ($classes) {
            list ($classHash, $ns, $classNames) = $this->createClasses($classes);
        }
        $manager = $this->createDefaultManager(null, $connector);
        if ($ns) {
            $manager->mapper->objectNamespace = $ns;
        }
        $this->prepareManager($manager, $classNames);
        return [$manager, $ns];
    }

    public function createDefaultArrayManager($map, $connector=null)
    {
        $mapper = new \Amiss\Mapper\Arrays($map);
        $config = [
            'dbTimeZone'=>'UTC',
        ];
        $mapper->typeHandlers = \Amiss\Sql\Factory::createTypeHandlers($config);
        $manager = $this->createDefaultManager($mapper, $connector);
        $this->prepareManager($manager, array_keys($map));
        return $manager;
    }

    protected function createDefaultManager($mapper=null, \PDOK\Connector $connector=null)
    {
        $connector = $connector ?: $this->getConnector();
        $info = $this->getConnectionInfo();
        $manager = \Amiss\Sql\Factory::createManager($connector, array(
            'dbTimeZone'=>'UTC',
            'mapper'=>$mapper,
        ));
        return $manager;
    }

    protected function prepareManager($manager, $classNames)
    {
        $connector = $manager->connector;
        $info = $this->getConnectionInfo();
        switch ($connector->engine) {
        case 'mysql':
            $connector->exec("DROP DATABASE IF EXISTS `{$info['dbName']}`");
            $connector->exec("CREATE DATABASE `{$info['dbName']}`");
            $connector->exec("USE `{$info['dbName']}`");
        break;
        case 'sqlite': break;
        default:
            throw new \Exception();
        }
        if ($classNames) {
            \Amiss\Sql\TableBuilder::create($manager->connector, $manager->mapper, $classNames);
        }
        return $manager;
    }
}

