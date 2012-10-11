<?php

require_once($amissPath.'/../doc/demo/model.php');

$cache = get_note_cache('apc', !isset($_GET['nocache']));

$mapper = new Amiss\Mapper\Note($cache);
$mapper->objectNamespace = 'Amiss\Demo';
$manager = new Amiss\Sql\Manager(new Amiss\Sql\Connector('sqlite::memory:'), $mapper);
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite.sql'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite.sql'));
