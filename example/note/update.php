<?php

use Amiss\Demo\Artist;

$artist = $manager->getById(Artist::class, 1);

$artist->name = 'foo bar';
$manager->update($artist);

$artist = $manager->getById(Artist::class, 1);
return $artist;
