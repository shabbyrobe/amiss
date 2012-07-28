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
        'Amiss\\Active\\Record'=>'Active/Record.php',
        'Amiss\\Connector'=>'Connector.php',
        'Amiss\\Criteria\\Query'=>'Criteria/Query.php',
        'Amiss\\Criteria\\Select'=>'Criteria/Select.php',
        'Amiss\\Criteria\\Update'=>'Criteria/Update.php',
        'Amiss\\Exception'=>'Exception.php',
        'Amiss\\Loader'=>'Loader.php',
        'Amiss\\Manager'=>'Manager.php',
        'Amiss\\Mapper'=>'Mapper.php',
        'Amiss\\Mapper\\Arrays'=>'Mapper/Arrays.php',
        'Amiss\\Mapper\\Base'=>'Mapper/Base.php',
        'Amiss\\Mapper\\Note'=>'Mapper/Note.php',
        'Amiss\\Meta'=>'Meta.php',
        'Amiss\\Name\\CamelToUnderscore'=>'Name/CamelToUnderscore.php',
        'Amiss\\Name\\Translator'=>'Name/Translator.php',
        'Amiss\\Note\\Parser'=>'Note/Parser.php',
        'Amiss\\Relator'=>'Relator.php',
        'Amiss\\Relator\\Association'=>'Relator/Association.php',
        'Amiss\\Relator\\Base'=>'Relator/Base.php',
        'Amiss\\Relator\\OneMany'=>'Relator/OneMany.php',
        'Amiss\\TableBuilder'=>'TableBuilder.php',
        'Amiss\\Type\\AutoGuid'=>'Type/AutoGuid.php',
        'Amiss\\Type\\Autoinc'=>'Type/Autoinc.php',
        'Amiss\\Type\\Date'=>'Type/Date.php',
        'Amiss\\Type\\Handler'=>'Type/Handler.php',
        'Amiss\\Type\\Identity'=>'Type/Identity.php',
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
