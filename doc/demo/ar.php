<?php

namespace Amiss\Demo\Active;

/**
 * @table artist
 */
class ArtistRecord extends \Amiss\Active\Record
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
	 * @var Amiss\Demo\Active\ArtistType
	 */
	private $type;
	
	/**
	 * @has one of=ArtistType; on=artistTypeId
	 */
	public function getType()
	{
		if ($this->type === null) {
			$this->type = $this->getRelated('type');
		}
		return $this->type;
	}
}

class ArtistType extends \Amiss\Active\Record
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
	 * @var Amiss\Demo\Active\ArtistRecord[]
	 */
	private $artists = null;
	
	/**
	 * @has many of=Artist
	 */
	public function getArtists()
	{
		if ($this->artists === null) {
			$this->artists = $this->getRelated('artists');
		}
		return $this->artists;
	}
}

/**
 * @table event
 */
class EventRecord extends \Amiss\Active\Record
{
	/**
	 * @primary
	 * @type autoinc
	 */
	public $eventId;
	
	/**
	 * @field
	 * @type datetime
	 */
	public $dateStart;
	
	/**
	 * @field
	 * @type datetime
	 */
	public $dateEnd;
	
	/**
	 * @field
	 */
	public $venueId;
	
	/**
	 * @field
	 */
	public $name;
	
	/**
	 * @field sub_name
	 */
	public $subName;
	
	/**
	 * @field
	 */
	public $slug;
	
	/**
	 * @var Amiss\Demo\Active\EventArtist[]
	 */
	private $eventArtists;
	
	/**
	 * @var Amiss\Demo\Active\VenueRecord
	 */
	private $venue;
	
	/**
	 * @has one of=Venue; on=venueId
	 */
	public function getVenue()
	{
		if (!$this->venue && $this->venueId) {
			$this->venue = $this->getRelated('venue');
		}
		return $this->venue;
	}
	
	/**
	 * @has many of=EventArtist
	 */
	public function getEventArtists()
	{
		if (!$this->eventArtists) {
			 $this->eventArtists = $this->getRelated('eventArtists');
		}
		return $this->eventArtists;
	}
}

class PlannedEvent extends EventRecord
{
	/**
	 * @field
	 * @type tinyint
	 */
	public $completeness;
	
	/**
	 * @has one of=VenueRecord; on=venueId
	 * Note: relations are not inherited by the note mapper
	 */
	public function getVenue()
	{
		return parent::getVenue();
	}
}

class EventArtist extends \Amiss\Active\Record
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
	 * @has one of=EventRecord; on=eventId
	 * @var Amiss\Demo\Active\EventRecord
	 */
	public $event;
	
	/**
	 * @has one of=ArtistRecord; on=artistId
	 * @var Amiss\Demo\Active\ArtistRecord
	 */
	public $artist;
}

/**
 * @table venue
 */
class VenueRecord extends \Amiss\Active\Record
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
