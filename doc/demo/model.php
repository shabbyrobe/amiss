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
	 * @has one ArtistType artistTypeId
	 */
	public $artistType;
}

class ArtistType extends Object
{
	/**
	 * @field
	 */
	public $artistTypeId;
	
	/**
	 * @field
	 */
	public $type;
	
	/**
	 * @field
	 */
	public $slug;
	
	/**
	 * @var Amiss\Demo\Artist[]
	 */
	public $artists = array();
}

class Event extends Object
{
	/**
	 * @field
	 */
	public $eventId;
	
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
	 */
	public $dateStart;
	
	/**
	 * @field
	 */
	public $dateEnd;
	
	/**
	 * @field
	 */
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

class EventArtist
{
	/**
	 * @primary
	 */
	public $eventId;
	
	/**
	 * @field
	 */
	public $artistId;
	
	/**
	 * @field
	 */
	public $priority;
	
	/**
	 * @field
	 */
	public $sequence;
	
	/**
	 * @field
	 */
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
