<?php

namespace Amiss\Demo;

abstract class Object
{
	public function __get($key)
	{
		throw new \Exception(sprintf("Property '%s' not defined", $key));
	}

	/**
	 * Prevents a field from being set if it is not defined in the class.
	 */
	public function __set($key, $value)
	{
		throw new \Exception(sprintf("Property '%s' not defined", $key));
	}

	/**
	 * Prevents a field from being tested for existence if it is not defined in the class.
	 */
	public function __isset($key)
	{
		throw new \Exception(sprintf("Property '%s' not defined", $key));
	}

	/**
	 * Prevents a field from being unset if it is not defined in the class. 
	 */
	public function __unset($key)
	{
		throw new \Exception(sprintf("Property '%s' not defined", $key));
	}
}

class Artist extends Object
{
	/**
	 * @field
	 * @primary
	 */
	public $artistId;
	
	/**
	 * @field
	 */
	public $artistTypeId;
	
	/**
	 * @field
	 */
	public $name;
	
	/**
	 * @field
	 */
	public $slug;
	
	/**
	 * @field
	 * @type LONGTEXT
	 */
	public $bio;
	
	/**
	 * @var Amiss\Demo\ArtistType
	 */
	public $artistType;
}

class ArtistType extends Object
{
	public $artistTypeId;
	public $type;
	public $slug;
	
	/**
	 * @var Amiss\Demo\Artist[]
	 */
	public $artists = array();
}

class Event extends Object
{
	public $eventId;
	public $name;
	public $slug;
	public $dateStart;
	public $dateEnd;
	public $venueId;
	
	/**
	 * @var Amiss\Demo\EventArtist[]
	 */
	public $eventArtists;
	
	/**
	 * @var Amiss\Demo\Venue
	 */
	public $venue;
}

class EventArtist extends Artist
{
	public $eventId;
	public $artistId;
	public $priority;
	public $sequence;
	public $eventArtistName;
	
	/**
	 * @var Amiss\Demo\Event
	 */
	public $event;
	
	/**
	 * @var Amiss\Demo\Artist
	 */
	public $artist;
}

class Venue extends Object
{
	/**
	 * @primary
	 * @field
	 */
	public $venueId;
	
	/**
	 * @field name
	 */
	public $venueName;
	
	/**
	 * @field slug
	 */
	public $venueSlug;
	
	/**
	 * @field address
	 */
	public $venueAddress;
	
	/**
	 * @field shortAddress
	 */
	public $venueShortAddress;
	
	/**
	 * @field latitude
	 */
	public $venueLatitude;
	
	/**
	 * @field longitude
	 */
	public $venueLongitude;
}
