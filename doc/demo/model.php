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
	 * @primary
	 * @type autoinc
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
	 * @type autoinc
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
	 * @type autoinc
	 */
	public $eventId;
	
	private $name;
	
	private $subName;
	
	private $slug;
	
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
	
	/**
	 * @field
	 */
	public function getSlug()
	{
		return $this->slug;
	}
	
	public function setSlug($value)
	{
		$this->slug = $value;
	}
	
	/**
	 * @field
	 */
	public function getName()
	{
		return $this->name;
	}
	
	public function setName($value)
	{
		$this->name = $value;
		if (!$this->slug) {
			$this->slug = trim(
				preg_replace('/-+/', '-', 
				preg_replace('/\s+/', '-', 
				preg_replace('/[^a-z0-9/', '', 
				strtolower(
					$value
				)))), 
				'-'
			);
		} 
	}
	
	/**
	 * @field sub_name
	 * @setter setTheSubName
	 */
	public function getSubName()
	{
		return $this->subName;
	}
	
	public function setTheSubName($value)
	{
		$this->subName = $value;
	}
}

class EventArtist
{
	/**
	 * @primary
	 */
	public $eventId;
	
	/**
	 * @primary
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
	 * @type autoinc
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
