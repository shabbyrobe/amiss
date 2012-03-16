<?php

use Amiss\Demo\Active\ArtistRecord;

$artist = ArtistRecord::getByPk(1);
dump($artist);

$artist->name = 'foo bar';
$artist->update();

$artist = ArtistRecord::getByPk(1);
return $artist;
