<?php

/**
 * Select list
 * 
 * Selects a list of all active records from a table
 */

use Amiss\Demo\Active\ArtistRecord;
$artist = ArtistRecord::getList();
return $artist;
