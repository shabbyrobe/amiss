<?php
$artist = $manager->getById(\Amiss\Demo\Artist::class, 1);
$manager->assignRelated($artist, 'artistType');
return $artist;
