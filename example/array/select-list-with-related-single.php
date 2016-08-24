<?php
$artists = $manager->getList(\Amiss\Demo\Artist::class);
$manager->assignRelated($artists, 'artistType');
return $artists;
