<?php
namespace Amiss\Sql;

use Amiss\Mapper;

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
        $mapper->identityHandlerId = !isset($config['identityHandlerId']) 
            ? Mapper::AUTOINC_TYPE
            : $config['identityHandlerId'];
        $mapper->typeHandlers = isset($config['typeHandlers']) 
            ? $config['typeHandlers'] 
            : static::createTypeHandlers($config);

        return $mapper;
    }
    
    public static function createRelators($config=null)
    {
        $relators = array();
        $oneMany = function($manager) use (&$oneMany) {
            return $oneMany = new \Amiss\Sql\Relator\OneMany($manager);
        };
        $relators['one']   = &$oneMany;
        $relators['many']  = &$oneMany;
        $relators['assoc'] = function($manager) {
            return new \Amiss\Sql\Relator\Association($manager);
        };
        return $relators;
    }
    
    public static function createTypeHandlers($config=null)
    {
        $handlers = array();
        
        if (isset($config['dbTimeZone'])  || 
            isset($config['appTimeZone']) || 
            isset($config['formats'])
        ) {
            throw new \InvalidArgumentException("Please use \$config['date'][...] instead of \$config[...] for date configuration");
        }
        
        if (isset($config['date'])) {
            $dateConfig = $config['date'];
            $handlers['date'] = function() use ($dateConfig) {
                $dateConfig['formats'] = 'date';
                $dateConfig['forceTime'] = '00:00:00';
                return new \Amiss\Sql\Type\Date($dateConfig);
            };

            $handlers['datetime'] = $handlers['timestamp'] = function() use ($dateConfig) {
                $dateConfig['formats'] = 'datetime';
                unset($dateConfig['forceTime']);
                return new \Amiss\Sql\Type\Date($dateConfig);
            };
            
            $handlers['unixtime'] = function() use ($dateConfig) {
                return \Amiss\Sql\Type\Date::unixTime($dateConfig);
            };
        }
        else {
            $handlers['date'] = $handlers['datetime'] = $handlers['timestamp'] = $handlers['unixtime'] = function() {
                throw new \UnexpectedValueException(
                    "Please pass dbTimeZone and appTimeZone with your \$config ".
                    "when using Amiss\Sql\Factory::createManager(), Amiss\Sql\Factory::createMapper() ".
                    "or Amiss\Sql\Factory::createTypeHandlers()"
                );
            };
        }
        
        $handlers['bool'] = $handlers['boolean'] = function() {
            return new \Amiss\Sql\Type\Boolean_();
        };

        $handlers['decimal'] = function() {
            return new \Amiss\Sql\Type\Decimal(
                isset($config['decimalPrecision']) ? $config['decimalPrecision'] : 65,
                isset($config['decimalScale'])     ? $config['decimalScale']     : 30
            );
        };
        
        $handlers[Mapper::AUTOINC_TYPE] = new \Amiss\Sql\Type\Autoinc();
        
        return $handlers;
    }
}
