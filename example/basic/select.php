<?php

use Amiss\Demo\Artist;
$artist = $manager->get('Artist', 'artistId=?', 1);
return $artist;
