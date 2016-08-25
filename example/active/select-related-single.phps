<?php
use Amiss\Demo\Active\ArtistRecord;
$artist = ArtistRecord::getById(1);
$type = $artist->getType();
return $artist;
