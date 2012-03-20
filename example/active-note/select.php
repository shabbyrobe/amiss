<?php

use Amiss\Example\ActiveNote;
$artist = ActiveNote\ArtistRecord::getByPk(1);
return $artist;
