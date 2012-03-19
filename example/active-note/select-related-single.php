<?php

$artist = ArtistRecord::getByPk(1);
$type = $artist->getType();
return $artist;
