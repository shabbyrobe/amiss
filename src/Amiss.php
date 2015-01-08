<?php
class Amiss
{
    public static function createSqlManager($connector, $config=null)
    {
        if ($config instanceof \Amiss\Mapper) {
            $config = array('mapper'=>$config);
        }
        if ($config === null) {
            $config = array();
        }
        
        $manager = new \Amiss\Sql\Manager($connector, isset($config['mapper']) ? $config['mapper'] : static::createSqlMapper($config));
        $manager->relators = isset($config['relators']) ? $config['relators'] : static::createSqlRelators($config);
        return $manager;
    }
    
    public static function createSqlMapper($config=null)
    {
        if ($config === null) {
            $config = array();
        }
        $mapper = new \Amiss\Mapper\Note(isset($config['cache']) ? $config['cache'] : null);
        $mapper->typeHandlers = isset($config['typeHandlers']) ? $config['typeHandlers'] : static::createSqlTypeHandlers($config);
        return $mapper;
    }
    
    public static function createSqlRelators($config=null)
    {
        if ($config == null) {
            $config = array();
        }
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
    
    public static function createSqlTypeHandlers($config=null)
    {
        if ($config == null) {
            $config = array();
        }
        
        $handlers = array();
        
        if (isset($config['dbTimeZone'])) {
            $config['appTimeZone'] = isset($config['appTimeZone']) ? $config['appTimeZone'] : null;
            
            $handlers['date'] = $handlers['datetime'] = $handlers['timestamp'] = function() use ($config) {
                return new \Amiss\Sql\Type\Date('datetime', $config['dbTimeZone'], $config['appTimeZone']);
            };
            
            $handlers['unixtime'] = function() use ($config) {
                return \Amiss\Sql\Type\Date::unixTime($config['appTimeZone']);
            };
        }
        else {
            $handlers['date'] = $handlers['datetime'] = $handlers['timestamp'] = $handlers['unixtime'] = function() {
                throw new \UnexpectedValueException("Please pass dbTimeZone (and optionally appTimeZone) with your \$config when using Amiss::createSqlManager(), Amiss::createSqlMapper() or Amiss::createSqlTypeHandlers()");
            };
        }
        
        $handlers['bool'] = $handlers['boolean'] = function() {
            return new \Amiss\Sql\Type\Bool();
        };
        
        $handlers['autoinc'] = new \Amiss\Sql\Type\Autoinc();
        
        return $handlers;
    }
}
