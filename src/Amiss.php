<?php
class Amiss
{
    public static $classes = array(
        'Amiss\Cache'=>'Cache.php',
        'Amiss\Exception'=>'Exception.php',
        'Amiss\Loader'=>'Loader.php',
        'Amiss\Mapper'=>'Mapper.php',
        'Amiss\Mapper\Arrays'=>'Mapper/Arrays.php',
        'Amiss\Mapper\Base'=>'Mapper/Base.php',
        'Amiss\Mapper\Note'=>'Mapper/Note.php',
        'Amiss\Meta'=>'Meta.php',
        'Amiss\Mongo\Connector'=>'Mongo/Connector.php',
        'Amiss\Mongo\Type\Date'=>'Mongo/Type/Date.php',
        'Amiss\Mongo\Type\Embed'=>'Mongo/Type/Embed.php',
        'Amiss\Mongo\Type\Id'=>'Mongo/Type/Id.php',
        'Amiss\Name\CamelToUnderscore'=>'Name/CamelToUnderscore.php',
        'Amiss\Name\Translator'=>'Name/Translator.php',
        'Amiss\Note\Parser'=>'Note/Parser.php',
        'Amiss\Sql\ActiveRecord'=>'Sql/ActiveRecord.php',
        'Amiss\Sql\Connector'=>'Sql/Connector.php',
        'Amiss\Sql\Criteria\Query'=>'Sql/Criteria/Query.php',
        'Amiss\Sql\Criteria\Select'=>'Sql/Criteria/Select.php',
        'Amiss\Sql\Criteria\Update'=>'Sql/Criteria/Update.php',
        'Amiss\Sql\Manager'=>'Sql/Manager.php',
        'Amiss\Sql\Relator'=>'Sql/Relator.php',
        'Amiss\Sql\Relator\Association'=>'Sql/Relator/Association.php',
        'Amiss\Sql\Relator\Base'=>'Sql/Relator/Base.php',
        'Amiss\Sql\Relator\OneMany'=>'Sql/Relator/OneMany.php',
        'Amiss\Sql\TableBuilder'=>'Sql/TableBuilder.php',
        'Amiss\Sql\Type\Autoinc'=>'Sql/Type/Autoinc.php',
        'Amiss\Sql\Type\Bool'=>'Sql/Type/Bool.php',
        'Amiss\Sql\Type\Date'=>'Sql/Type/Date.php',
        'Amiss\Type\AutoGuid'=>'Type/AutoGuid.php',
        'Amiss\Type\Embed'=>'Type/Embed.php',
        'Amiss\Type\Encoder'=>'Type/Encoder.php',
        'Amiss\Type\Handler'=>'Type/Handler.php',
        'Amiss\Type\Identity'=>'Type/Identity.php',
    );
    
    public static function createManager($connector, $config=null)
    {
        if ($config === null)
            $config = array();
        
        $manager = new \Amiss\Sql\Manager($connector, isset($config['mapper']) ? $config['mapper'] : static::createMapper($config));
        $manager->relators = isset($config['relators']) ? $config['relators'] : static::createRelators($config);
        return $manager;
    }
    
    public static function createMapper($config)
    {
        $mapper = new \Amiss\Mapper\Note(isset($config['cache']) ? $config['cache'] : null);
        $mapper->typeHandlers = isset($config['typeHandlers']) ? $config['typeHandlers'] : static::createTypeHandlers($config);
        return $mapper;
    }
    
    public static function createRelators($config)
    {
        return array(
            'one'=>function($manager) {
                return new \Amiss\Sql\Relator\OneMany($manager);
            },
            'many'=>function($manager) {
                return new \Amiss\Sql\Relator\OneMany($manager);
            },
            'assoc'=>function($manager) {
                return new \Amiss\Sql\Relator\Assoc($manager);
            }
        );
    }
    
    public static function createTypeHandlers($config)
    {
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
                throw new \UnexpectedValueException("Please pass dbTimeZone (and optionally appTimeZone) with your \$config when using Amiss::createManager(), Amiss::createMapper() or Amiss::createTypeHandlers()");
            };
        }
        
        $handlers['bool'] = $handlers['boolean'] = function() {
            return new \Amiss\Sql\Type\Bool();
        };
        
        $handlers['autoinc'] = new \Amiss\Sql\Type\Autoinc();
        
        return $handlers;
    }
    
    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'load'));
    }

    public static function load($class)
    {
        if (isset(static::$classes[$class])) {
            require __DIR__.'/Amiss/'.static::$classes[$class];
            return true;
        }
    }
}
