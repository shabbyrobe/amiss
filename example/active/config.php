<?php

require_once(__DIR__.'/../../doc/demo/ar.php');

$connector = new \PDOK\Connector('sqlite::memory:');
$manager = Amiss\Sql\Factory::createManager($connector, array(
    'cache'=>get_note_cache(),
    'typeHandlers'=>array(),
));

$manager->mapper->objectNamespace = 'Amiss\Demo\Active';
$connector->exec(file_get_contents(__DIR__.'/../../doc/demo/schema.sqlite.sql'));
$connector->exec(file_get_contents(__DIR__.'/../../doc/demo/testdata.sql'));

Amiss\Sql\ActiveRecord::setManager($manager);
