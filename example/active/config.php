<?php

require_once($amissPath.'/../doc/demo/ar.php');

$cache = get_note_cache('xcache', !isset($_GET['nocache']));
$mapper = new Amiss\Mapper\Note($cache);
$mapper->objectNamespace = 'Amiss\Demo\Active';
$manager = new Amiss\Sql\Manager(new Amiss\Sql\Connector('sqlite::memory:'), $mapper);
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite.sql'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite.sql'));

Amiss\Sql\ActiveRecord::setManager($manager);
