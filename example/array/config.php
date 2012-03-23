<?php

require_once($amissPath.'/../doc/demo/model.php');

$namespace = 'Amiss\Demo';
$mapper = new Amiss\Mapper\Arrays(array(
	$namespace.'\Artist'=>array(
		'primary'=>array('artistId'),
		'fields'=>array(
			'artistId'=>array('type'=>'autoinc'),
			'artistTypeId'=>array(),
			'name'=>array(),
			'slug'=>array(),
			'bio'=>array(),
		),
		'relations'=>array(
			'artistType'=>array('one', 'of'=>'ArtistType', 'on'=>'artistTypeId'),
			'events'=>array('assoc', 'of'=>'Event', 'via'=>'EventArtist'),
		),
	),
	$namespace.'\ArtistType'=>array(
		'table'=>'artist_type',
		'primary'=>array('artistTypeId'),
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
$manager = new Amiss\Manager(new Amiss\Connector('sqlite::memory:'), $mapper);
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite'));
