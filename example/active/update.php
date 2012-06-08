<?php

use Amiss\Demo\Active\ArtistRecord;

$artist = ArtistRecord::getById(1);
dump($artist);

$artist->name = 'foo bar';
$artist->update();

$artist = ArtistRecord::getById(1);
return $artist;
