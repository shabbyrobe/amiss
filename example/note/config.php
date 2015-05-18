<?php

require_once($amissPath.'/../doc/demo/model.php');

$connector = new \PDOK\Connector('sqlite::memory:');
$manager = Amiss\Sql\Factory::createManager($connector, array(
    'cache'=>get_note_cache(),
    'typeHandlers'=>array(),
));

$manager->mapper->objectNamespace = 'Amiss\Demo';

$connector->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite.sql'));
$connector->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sql'));
