<?php

use Amiss\Demo\Active\ArtistRecord;
$artist = ArtistRecord::getByPk(1);
$type = $artist->getType();
return $artist;
