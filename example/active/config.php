<?php

require_once($amissPath.'/../doc/demo/ar.php');

$cache = get_note_cache('xcache', !isset($_GET['nocache']));

$connector = new Amiss\Sql\Connector('sqlite::memory:');
$manager = Amiss::createManager($connector, array(
    'cache'=>$cache,
));

$manager->mapper->objectNamespace = 'Amiss\Demo\Active';
$connector->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite.sql'));
$connector->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite.sql'));

Amiss\Sql\ActiveRecord::setManager($manager);
