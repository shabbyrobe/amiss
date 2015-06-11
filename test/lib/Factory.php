<?php
namespace Amiss\Test;

use Amiss\Sql\TableBuilder;
use Amiss\Test\Helper\ClassBuilder;

/**
 * Welcome to the world of manual memory management in PHP.
 * Don't hold on to any references to classes returned by this.
 * If you assign them to instance properties on a test case, you
 * MUST null them in the tearDown. Things get weird when you leak
 * references to this stuff.
 *
 * The old way of managing this stuff was built off a complex
 * and cumbersome inheritance hierarchy which didn't really work
 * very well. This appears to be an improvement because you can
 * at least use it for one-shot tests where everything falls out 
 * of scope at the end of the function, in setUp/tearDown pairs
 * and in @depends chains.
 */
class Factory
{
    public static function managerNoteDefault($mapper=null)
    {
        $cf = new ConnectionFactory(\Amiss\Test\Helper\Env::instance()->getConnectionInfo());
        if (!$mapper) {
            $mapper = \Amiss\Sql\Factory::createMapper(array(
                'dbTimeZone'=>'UTC',
            ));
        }
        $connector = $cf->getConnector();
        $manager = \Amiss\Sql\Factory::createManager($connector, $mapper);
        return (object)[
            'mapper'=>$mapper,
            'manager'=>$manager,
            'connector'=>$connector,

            // We have to hold on to this otherwise it goes out of scope and
            // gets destructed. I would not recommend using it
            '_connectionFactory'=>$cf,
        ];
    }

    public static function managerNoteModelCustom($classes, $deps=null)
    {
        $deps = $deps ?: self::managerNoteDefault();
        list ($ns, $classNames) = ClassBuilder::i()->register($classes);
        if ($ns) {
            $deps->mapper->objectNamespace = $ns;
            $deps->ns = $ns;
        }
        TableBuilder::create($deps->connector, $deps->mapper, $classNames);
        return $deps;
    }

    public static function managerArraysModelCustom($map)
    {
        $mapper = new \Amiss\Mapper\Arrays($map);
        $config = [
            'dbTimeZone'=>'UTC',
        ];
        $mapper->typeHandlers = \Amiss\Sql\Factory::createTypeHandlers($config);
        $deps = self::managerNoteDefault($mapper);
        TableBuilder::create($deps->connector, $mapper, array_keys($map));
        return $deps;
    }

    public static function managerModelDemo()
    {
        $deps = self::managerNoteDefault();
        $deps->mapper->objectNamespace = 'Amiss\Demo';

        $connector = $deps->connector;
        $connector->exec(file_get_contents(AMISS_BASE_PATH."/doc/demo/schema.{$connector->engine}.sql"));
        $connector->exec(file_get_contents(AMISS_BASE_PATH.'/doc/demo/testdata.sql'));
        $connector->queries = 0;
        
        return $deps;
    }

    public static function managerActiveDemo()
    {
        $deps = new Helper\SelfDestructing(self::managerNoteDefault(), function() {
            \Amiss\Demo\Active\DemoRecord::_reset();
        });
        $deps->mapper->objectNamespace = 'Amiss\Demo\Active';

        \Amiss\Demo\Active\DemoRecord::setManager($deps->manager);

        $connector = $deps->connector;
        $connector->exec(file_get_contents(AMISS_BASE_PATH."/doc/demo/schema.{$connector->engine}.sql"));
        $connector->exec(file_get_contents(AMISS_BASE_PATH.'/doc/demo/testdata.sql'));
        $connector->queries = 0;
        
        return $deps;
    }
}
