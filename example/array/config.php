<?php

require_once(__DIR__.'/../../doc/demo/model.php');

$namespace = 'Amiss\Demo';
$mapper = new Amiss\Mapper\Arrays(array(
    $namespace.'\Artist'=>array(
        'primary'=>array('artistId'),
        'fields'=>array(
            'artistId'=>array('type'=>'autoinc'),
            'artistTypeId'=>['index'=>true],
            'name'=>true,
            'slug'=>true,
            'bio'=>true,
        ),
        'relations'=>array(
            'artistType'=>array('one', 'of'=>'ArtistType', 'from'=>'artistTypeId'),
            'events'=>array('assoc', 'of'=>'Event', 'via'=>'EventArtist'),
        ),
    ),
    $namespace.'\ArtistType'=>array(
        'table'=>'artist_type',
        'primary'=>'artistTypeId',
        'fields'=>array(
            'artistTypeId'=>array('type'=>'autoinc'),
            'type'=>array(),
        ),
        'relations'=>array(
            'artists'=>array('many', 'of'=>'Artist'),
        ),
    ),
));
$mapper->objectNamespace = $namespace;

$connector = new \PDOK\Connector('sqlite::memory:');
$manager = Amiss\Sql\Factory::createManager($connector, array(
    'mapper'=>$mapper,
));
$connector->exec(file_get_contents(__DIR__.'/../../doc/demo/schema.sqlite.sql'));
$connector->exec(file_get_contents(__DIR__.'/../../doc/demo/testdata.sql'));
