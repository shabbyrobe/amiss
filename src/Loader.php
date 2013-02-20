<?php
namespace Amiss;

/*
class Loader
{
    public $namespace;
    public $path;
    private $nslen;
    
    public static function register($namespace='Amiss\\', $path=__DIR__)
    {
        $class = __CLASS__;
        spl_autoload_register(array(new $class($namespace, $path), 'load'));
    }
    
    public function __construct($namespace, $path)
    {
        $this->namespace = $namespace;
        $this->nslen = strlen($namespace);
        $this->path = $path;
    }
    
    public function load($class)
    {
        if (strpos($class, $this->namespace)===0) {
            require($this->path.'/'.str_replace('\\', '/', str_replace('..', '', substr($class, $this->nslen))).'.php');
            return true;
        }
    }
}
//*/

///*
class Loader
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
        'Amiss\Mongo\TypeSet'=>'Mongo/TypeSet.php',
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
        'Amiss\Sql\TypeSet'=>'Sql/TypeSet.php',
        'Amiss\Type\AutoGuid'=>'Type/AutoGuid.php',
        'Amiss\Type\Embed'=>'Type/Embed.php',
        'Amiss\Type\Encoder'=>'Type/Encoder.php',
        'Amiss\Type\Handler'=>'Type/Handler.php',
        'Amiss\Type\Identity'=>'Type/Identity.php',
    );
    
    public static function register($path=__DIR__)
    {
        $class = __CLASS__;
        spl_autoload_register(array(new $class($path), 'load'));
    }

    public function __construct($path)
    {
        $this->path = $path;
    }
    
    public function load($class)
    {
        if (isset(static::$classes[$class])) {
            require $this->path.'/'.static::$classes[$class];
            return true;
        }
    }   
}
//*/
