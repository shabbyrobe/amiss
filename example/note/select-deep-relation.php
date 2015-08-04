<?php

$events = $manager->getList(\Amiss\Demo\Event::class);
    
// Relation 1: populate each Event object's list of EventArtists
$manager->assignRelated($events, 'eventArtists');

// Relation 2: populate each EventArtist object's artist property
$eventArtists = \Amiss\Functions::getChildren($events, 'eventArtists');
$manager->assignRelated($eventArtists, 'artist');

// Relation 3: populate each Artist object's artistType property
$manager->assignRelated(\Amiss\Functions::getChildren($eventArtists, 'artist'), 'artistType');
    
return $events;
