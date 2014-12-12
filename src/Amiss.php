<?php
class Amiss
{
    public static $classes = array(
        'Amiss\Cache'=>'Amiss/Cache.php',
        'Amiss\Exception'=>'Amiss/Exception.php',
        'Amiss\Ext\NestedSet\ActiveRecord'=>'Amiss/Ext/NestedSet/ActiveRecord.php',
        'Amiss\Ext\NestedSet\Manager'=>'Amiss/Ext/NestedSet/Manager.php',
        'Amiss\Ext\NestedSet\ParentRelator'=>'Amiss/Ext/NestedSet/ParentRelator.php',
        'Amiss\Ext\NestedSet\ParentsRelator'=>'Amiss/Ext/NestedSet/ParentsRelator.php',
        'Amiss\Ext\NestedSet\TreeRelator'=>'Amiss/Ext/NestedSet/TreeRelator.php',
        'Amiss\Mapper'=>'Amiss/Mapper.php',
        'Amiss\Mapper\Arrays'=>'Amiss/Mapper/Arrays.php',
        'Amiss\Mapper\Base'=>'Amiss/Mapper/Base.php',
        'Amiss\Mapper\Note'=>'Amiss/Mapper/Note.php',
        'Amiss\Meta'=>'Amiss/Meta.php',
        'Amiss\Mongo\Connector'=>'Amiss/Mongo/Connector.php',
        'Amiss\Mongo\TypeSet'=>'Amiss/Mongo/TypeSet.php',
        'Amiss\Mongo\Type\Date'=>'Amiss/Mongo/Type/Date.php',
        'Amiss\Mongo\Type\Id'=>'Amiss/Mongo/Type/Id.php',
        'Amiss\Name\CamelToUnderscore'=>'Amiss/Name/CamelToUnderscore.php',
        'Amiss\Name\Translator'=>'Amiss/Name/Translator.php',
        'Amiss\Note\Parser'=>'Amiss/Note/Parser.php',
        'Amiss\Sql\ActiveRecord'=>'Amiss/Sql/ActiveRecord.php',
        'Amiss\Sql\Connector'=>'Amiss/Sql/Connector.php',
        'Amiss\Sql\Query\Criteria'=>'Amiss/Sql/Query/Criteria.php',
        'Amiss\Sql\Query\Insert'=>'Amiss/Sql/Query/Insert.php',
        'Amiss\Sql\Query\Select'=>'Amiss/Sql/Query/Select.php',
        'Amiss\Sql\Query\Update'=>'Amiss/Sql/Query/Update.php',
        'Amiss\Sql\Query'=>'Amiss/Sql/Query.php',
        'Amiss\Sql\Engine\MySQL\TableBuilder'=>'Amiss/Sql/Engine/MySQL/TableBuilder.php',
        'Amiss\Sql\Engine\SQLite\TableBuilder'=>'Amiss/Sql/Engine/SQLite/TableBuilder.php',
        'Amiss\Sql\Manager'=>'Amiss/Sql/Manager.php',
        'Amiss\Sql\Relator'=>'Amiss/Sql/Relator.php',
        'Amiss\Sql\Relator\Association'=>'Amiss/Sql/Relator/Association.php',
        'Amiss\Sql\Relator\Base'=>'Amiss/Sql/Relator/Base.php',
        'Amiss\Sql\Relator\OneMany'=>'Amiss/Sql/Relator/OneMany.php',
        'Amiss\Sql\TableBuilder'=>'Amiss/Sql/TableBuilder.php',
        'Amiss\Sql\Type\Autoinc'=>'Amiss/Sql/Type/Autoinc.php',
        'Amiss\Sql\Type\Bool'=>'Amiss/Sql/Type/Bool.php',
        'Amiss\Sql\Type\Date'=>'Amiss/Sql/Type/Date.php',
        'Amiss\Type\AutoGuid'=>'Amiss/Type/AutoGuid.php',
        'Amiss\Type\Embed'=>'Amiss/Type/Embed.php',
        'Amiss\Type\Encoder'=>'Amiss/Type/Encoder.php',
        'Amiss\Type\Handler'=>'Amiss/Type/Handler.php',
        'Amiss\Type\Identity'=>'Amiss/Type/Identity.php',
    );
    
    public static function register()
    {
        spl_autoload_register(array(__CLASS__, 'load'));
    }

    public static function load($class)
    {
        if (isset(static::$classes[$class])) {
            require __DIR__.'/'.static::$classes[$class];
            return true;
        }
    }
    
    public static function createSqlManager($connector, $config=null)
    {
        if ($config instanceof \Amiss\Mapper)
            $config = array('mapper'=>$config);
        if ($config === null)
            $config = array();
        
        $manager = new \Amiss\Sql\Manager($connector, isset($config['mapper']) ? $config['mapper'] : static::createSqlMapper($config));
        $manager->relators = isset($config['relators']) ? $config['relators'] : static::createSqlRelators($config);
        return $manager;
    }
    
    public static function createSqlMapper($config=null)
    {
        if ($config === null)
            $config = array();
        
        $mapper = new \Amiss\Mapper\Note(isset($config['cache']) ? $config['cache'] : null);
        $mapper->typeHandlers = isset($config['typeHandlers']) ? $config['typeHandlers'] : static::createSqlTypeHandlers($config);
        return $mapper;
    }
    
    public static function createSqlRelators($config=null)
    {
        if ($config == null)
            $config = array();
        
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
        if ($config == null)
            $config = array();
        
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
