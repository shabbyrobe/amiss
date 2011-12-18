<?php

require_once($amissPath.'/../doc/demo/ar.php');

$manager = new Amiss\Manager(new Amiss\Connector('sqlite::memory:'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/schema.sqlite'));
$manager->getConnector()->exec(file_get_contents($amissPath.'/../doc/demo/testdata.sqlite'));

Amiss\Active\Record::setManager($manager);
