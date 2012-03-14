<?php

$artist = $manager->getByPk('Artist', 1);
$type = $manager->getRelated($artist, 'type', 'artstTypeId');
return $artists;
