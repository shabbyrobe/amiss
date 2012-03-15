<?php

$artist = $manager->getByPk('Artist', 1);
$manager->assignRelated($artist, 'artistType');
return $artist;
