<?php

require_once($amissPath.'/../doc/demo/ar.php');

$cache = get_note_cache('apc', !isset($_GET['nocache']));
$mapper = new Amiss\Mapper\Note($cache);
$mapper->objectNamespace = 'Amiss\Demo\Active';
$manager = new Amiss\Manager(new Amiss\Connector('sqlite::memory:'), $mapper);
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite.sql'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite.sql'));

Amiss\Active\Record::setManager($manager);
