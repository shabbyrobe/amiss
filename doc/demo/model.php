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
	 * @has one ArtistType artistTypeId
	 * @var Amiss\Demo\ArtistType
	 */
	public $artistType;
}

class ArtistType extends Object
{
	/**
	 * @primary
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
	 * @has many Artist
	 * @var Amiss\Demo\Artist[]
	 */
	public $artists = array();
}

class Event extends Object
{
	/**
	 * @primary
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
	 * @has many EventArtist
	 * @var Amiss\Demo\EventArtist[]
	 */
	public $eventArtists;
	
	/**
	 * @has one Venue venueId
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
	 * @has one Event eventId
	 * @var Amiss\Demo\Event
	 */
	public $event;
	
	/**
	 * @has one Artist artistId
	 * @var Amiss\Demo\Artist
	 */
	public $artist;
}

class Venue extends Object
{
	/**
	 * @primary
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
