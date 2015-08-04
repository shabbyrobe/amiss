<?php

require_once(__DIR__.'/../../doc/demo/model.php');

$connector = new \PDOK\Connector('sqlite::memory:');
$manager = Amiss\Sql\Factory::createManager($connector, array(
    'cache'=>get_note_cache(),
    'typeHandlers'=>array(),
));

$connector->exec(file_get_contents(__DIR__.'/../../doc/demo/schema.sqlite.sql'));
$connector->exec(file_get_contents(__DIR__.'/../../doc/demo/testdata.sql'));
