<?php

require_once($amissPath.'/../doc/demo/model.php');

$connector = new Amiss\Sql\Connector('sqlite::memory:');
$manager = Amiss::createSqlManager($connector, array(
    'cache'=>get_note_cache(),
    'typeHandlers'=>array(),
));

$manager->mapper->objectNamespace = 'Amiss\Demo';

$connector->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite.sql'));
$connector->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sql'));
