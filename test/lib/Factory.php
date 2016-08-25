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
    public static function managerNoteDefault($deps=null)
    {
        if (!$deps) {
            $deps = (object)[];
        }

        $cf = new ConnectionFactory(\Amiss\Test\Helper\Env::instance()->getConnectionInfo());
        if (!isset($deps->mapper) || !$deps->mapper) {
            $config = [
                'date' => ['dbTimeZone' => 'UTC', 'appTimeZone' => 'UTC'],
            ];
            $deps->mapper = \Amiss\Sql\Factory::createMapper($config);
        }
        if (!isset($deps->connector) || !$deps->connector) {
            $deps->connector = $cf->getConnector();
        }

        $deps->manager = \Amiss\Sql\Factory::createManager($deps->connector, $deps->mapper);
        $deps->_connectionFactory = $cf;
        return $deps;
    }

    public static function managerNoteModelCustom($classes, $deps=null)
    {
        $deps = self::managerNoteDefault($deps);
        list ($ns, $classNames, $deps->classes) = ClassBuilder::i()->register($classes);
        if ($ns) {
            $deps->ns = $ns;
        }
        TableBuilder::create($deps->connector, $deps->mapper, $classNames);
        return $deps;
    }

    public static function managerArraysModelCustom($map)
    {
        $mapper = new \Amiss\Mapper\Arrays($map);
        $config = ['date' => ['dbTimeZone' => 'UTC', 'appTimeZone' => 'UTC']];

        $deps = (object)['mapper' => $mapper];

        $mapper->typeHandlers = \Amiss\Sql\Factory::createTypeHandlers($config);
        $deps = self::managerNoteDefault($deps);
        TableBuilder::create($deps->connector, $mapper, array_keys($map));
        return $deps;
    }

    public static function managerModelDemo()
    {
        $deps = self::managerNoteDefault();

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

        \Amiss\Demo\Active\DemoRecord::setManager($deps->manager);

        $connector = $deps->connector;
        $connector->exec(file_get_contents(AMISS_BASE_PATH."/doc/demo/schema.{$connector->engine}.sql"));
        $connector->exec(file_get_contents(AMISS_BASE_PATH.'/doc/demo/testdata.sql'));
        $connector->queries = 0;
        
        return $deps;
    }
}
