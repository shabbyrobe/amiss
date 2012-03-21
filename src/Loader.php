<?php

namespace Amiss;

class Loader
{
	public $namespace;
	public $path;
	private $nslen;
	
	public static function register()
	{
		$class = __CLASS__;
		spl_autoload_register(array(new $class, 'load'));
	}
	
	public function __construct($namespace='Amiss\\', $path=__DIR__)
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
