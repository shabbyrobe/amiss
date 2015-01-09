<?php

require_once($amissPath.'/../doc/demo/model.php');

$namespace = 'Amiss\Demo';
$mapper = new Amiss\Mapper\Arrays(array(
    $namespace.'\Artist'=>array(
        'primary'=>array('artistId'),
        'fields'=>array(
            'artistId'=>array('type'=>'autoinc'),
            'artistTypeId',
            'name',
            'slug',
            'bio',
        ),
        'relations'=>array(
            'artistType'=>array('one', 'of'=>'ArtistType', 'on'=>'artistTypeId'),
            'events'=>array('assoc', 'of'=>'Event', 'via'=>'EventArtist'),
        ),
    ),
    $namespace.'\ArtistType'=>array(
        'table'=>'artist_type',
        'primary'=>'artistTypeId',
        'fields'=>array(
            'artistTypeId'=>array('type'=>'autoinc'),
            'type'=>array(),
            'slug'=>array(),
        ),
        'relations'=>array(
            'artists'=>array('many', 'of'=>'Artist'),
        ),
    ),
));
$mapper->objectNamespace = $namespace;

$connector = new Amiss\Sql\Connector('sqlite::memory:');
$manager = Amiss\Factory::createSqlManager($connector, array(
    'mapper'=>$mapper,
));
$connector->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite.sql'));
$connector->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sql'));
