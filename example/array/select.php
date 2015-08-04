<?php

use Amiss\Demo\Artist;
$artist = $manager->get(Artist::class, 'artistId=?', [1]);
return $artist;
