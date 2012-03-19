<?php

require_once($amissPath.'/../doc/demo/model.php');

$cache = null;
$cacheType = 'hack';

if (!isset($_GET['nocache'])) {
	if ($cacheType == 'hack') {
		$path = sys_get_temp_dir();
		$cache = array(
			function ($key) use ($path) {
				$key = md5($key);
				$file = $path.'/nc-'.$key;
				if (file_exists($file)) {
					return unserialize(file_get_contents($file));
				}
			},
			function ($key, $value) use ($path) {
				$key = md5($key);
				$file = $path.'/nc-'.$key;
				file_put_contents($file, serialize($value));
			}
		);
	}
	elseif ($cacheType == 'apc') {
		$cache = 'apc';
	}
}

$mapper = new Amiss\Mapper\Note($cache);
$mapper->objectNamespace = 'Amiss\Demo';
$manager = new Amiss\Manager(new Amiss\Connector('sqlite::memory:'), $mapper);
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite'));

Amiss\Active\Record::setManager($manager);
