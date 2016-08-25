<?php
$artists = $manager->getList('Artist');
$manager->assignRelated($artists, 'artistType');
return $artists;
