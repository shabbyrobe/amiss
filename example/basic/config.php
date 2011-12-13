<?php

require_once($amissPath.'/../doc/demo/model.php');

$manager = new Amiss\Manager(new Amiss\Connector('sqlite::memory:'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite'));
$manager->objectNamespace = 'Amiss\Demo';

Amiss\Active\Record::setManager($manager);
