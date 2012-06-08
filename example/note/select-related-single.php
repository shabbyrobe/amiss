<?php

$artist = $manager->getById('Artist', 1);
$manager->assignRelated($artist, 'artistType');
return $artist;
