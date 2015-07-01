<?php
namespace Amiss\Sql;

class Factory
{
    public static function createManager($connector, $config=null)
    {
        if ($config instanceof \Amiss\Mapper) {
            $config = array('mapper'=>$config);
        }
        $manager = new \Amiss\Sql\Manager($connector, isset($config['mapper']) ? $config['mapper'] : static::createMapper($config));
        $manager->relators = isset($config['relators']) ? $config['relators'] : static::createRelators($config);
        return $manager;
    }
    
    public static function createMapper($config=null)
    {
        $mapper = new \Amiss\Mapper\Note(
            isset($config['cache']) ? $config['cache'] : null
        );
        $mapper->typeHandlers = isset($config['typeHandlers']) ? $config['typeHandlers'] : static::createTypeHandlers($config);
        return $mapper;
    }
    
    public static function createRelators($config=null)
    {
        $relators = array();
        $oneMany = function($manager) use (&$oneMany) {
            return $oneMany = new \Amiss\Sql\Relator\OneMany($manager);
        };
        $relators['one'] = &$oneMany;
        $relators['many'] = &$oneMany;
        $relators['assoc'] = function($manager) {
            return new \Amiss\Sql\Relator\Association($manager);
        };
        return $relators;
    }
    
    public static function createTypeHandlers($config=null)
    {
        $handlers = array();
        
        if (isset($config['dbTimeZone'])) {
            $config['appTimeZone'] = isset($config['appTimeZone']) ? $config['appTimeZone'] : null;

            $handlers['date'] = function() use ($config) {
                return new \Amiss\Sql\Type\Date([
                    'formats'=>'date',
                    'dbTimeZone'=>$config['dbTimeZone'], 
                    'appTimeZone'=>$config['appTimeZone'],
                    'forceTime'=>'00:00:00'
                ]);
            };
            
            $handlers['datetime'] = $handlers['timestamp'] = function() use ($config) {
                return new \Amiss\Sql\Type\Date([
                    'formats'=>'datetime', 
                    'dbTimeZone'=>$config['dbTimeZone'], 
                    'appTimeZone'=>$config['appTimeZone'],
                ]);
            };
            
            $handlers['unixtime'] = function() use ($config) {
                return \Amiss\Sql\Type\Date::unixTime($config['appTimeZone']);
            };
        }
        else {
            $handlers['date'] = $handlers['datetime'] = $handlers['timestamp'] = $handlers['unixtime'] = function() {
                throw new \UnexpectedValueException(
                    "Please pass dbTimeZone (and optionally appTimeZone) with your \$config ".
                    "when using Amiss\Sql\Factory::createManager(), Amiss\Sql\Factory::createMapper() ".
                    "or Amiss\Sql\Factory::createTypeHandlers()"
                );
            };
        }
        
        $handlers['bool'] = $handlers['boolean'] = function() {
            return new \Amiss\Sql\Type\Boolean();
        };

        $handlers['decimal'] = function() {
            return new \Amiss\Sql\Type\Decimal(
                isset($config['decimalPrecision']) ? $config['decimalPrecision'] : 65,
                isset($config['decimalScale'])     ? $config['decimalScale']     : 30
            );
        };
        
        $handlers['autoinc'] = new \Amiss\Sql\Type\Autoinc();
        
        return $handlers;
    }
}
