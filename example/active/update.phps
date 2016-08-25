<?php
use Amiss\Demo\Active\ArtistRecord;

$artist = ArtistRecord::getById(1);

$artist->name = 'foo bar';
$artist->update();

$artist = ArtistRecord::getById(1);
return $artist;
