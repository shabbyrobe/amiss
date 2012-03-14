<?php

require_once($amissPath.'/../doc/demo/model.php');

$mapper = new Amiss\Mapper\Note('apc');
$mapper->objectNamespace = 'Amiss\Demo';
$manager = new Amiss\Manager(new Amiss\Connector('sqlite::memory:'), $mapper);
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite'));

Amiss\Active\Record::setManager($manager);
