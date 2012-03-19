<?php

namespace Amiss\Demo\Active;

class ArtistRecord extends \Amiss\Active\Record
{
	public static $table = 'artist';
	public static $primary = 'artistId';
	public static $fields = array(
		'artistTypeId', 'name', 'slug', 'bio'
	);
	
	public $artistId;
	public $artistTypeId;
	public $name;
	public $slug;
	public $bio;
	
	/**
	 * @var Amiss\Demo\ArtistTypeRecord
	 */
	private $type;
	
	public function getType()
	{
		if ($this->type === null) {
			$this->type = $this->getRelated('type');
		}
		return $this->type;
	}
	
	public static $relations = array(
		'type'=>array('one'=>'ArtistType', 'on'=>'artistTypeId'),
	);
}

class ArtistType extends \Amiss\Active\Record
{
	public static $fields = array(
		'artistTypeId', 'type', 'slug'
	);
	
	public $artistTypeId;
	public $type;
	public $slug;
	
	/**
	 * @var Amiss\Demo\ArtistRecord[]
	 */
	private $artists = null;
	
	public function getArtists()
	{
		if ($this->artists === null) {
			$this->artists = $this->getRelated('artists');
		}
		return $this->artists;
	}
	
	public static function getRelations()
	{
		return array(
			'artists'=>array('many'=>'ArtistRecord', 'on'=>'artistTypeId', 'getter'=>'getArtists'),
		);
	}
}

class EventRecord extends \Amiss\Active\Record
{
	public static $table = 'event';
	public static $primary = 'eventId';
	public static $fields = array(
		'name'=>'varchar(50)',
		'sub_name',
		'slug', 
		'dateStart'=>'datetime', 
		'dateEnd'=>'datetime',
		'venueId'
	);
	
	public $eventId;
	public $name;
	
	// statics mapper doesn't support translating property names explicitly yet  
	public $sub_name;
	
	public $slug;
	public $dateStart;
	public $dateEnd;
	public $venueId;
	
	/**
	 * @var Amiss\Demo\EventArtistRecord[]
	 */
	public $eventArtists;
	
	/**
	 * @var Amiss\Demo\VenueRecord
	 */
	private $venue;
	
	public function getVenue()
	{
		if (!$this->venue && $this->venueId) {
			$this->venue = $this->getRelated('venue');
		}
		return $this->venue;
	}
	
	public function getEventArtists()
	{
		if (!$this->eventArtists) {
			 $this->eventArtists = $this->getRelated('eventArtists');
		}
		return $this->eventArtists;
	}
	
	public static $relations = array(
		'eventArtists'=>array('many'=>'EventArtist', 'on'=>'eventId'),
		'venue'=>array('one'=>'VenueRecord', 'on'=>'venueId'),
	);
}

class PlannedEvent extends EventRecord
{
	public static $table = 'planned_event';
	
	public static $fields = array(
		'completeness'=>'tinyint',
	);
	
	public static $relations = array(
		'venue'=>array('one'=>'VenueRecord', 'on'=>'venueId'),
	);
}

class EventArtist extends \Amiss\Active\Record
{
	public static $fields = array(
		'eventId', 'artistId', 'priority', 'sequence', 'eventArtistName',
	);
	
	public $eventId;
	public $artistId;
	public $priority;
	public $sequence;
	public $eventArtistName;
	
	/**
	 * @var Amiss\Demo\EventRecord
	 */
	public $event;
	
	/**
	 * @var Amiss\Demo\ArtistRecord
	 */
	public $artist;
	
	public static $relations = array(
		'event'=>array('one'=>'EventRecord', 'on'=>'eventId'),
		'artist'=>array('one'=>'ArtistRecord', 'on'=>'artistId'),
	);
}

class VenueRecord extends \Amiss\Active\Record
{
	public static $fields = array(
		'venueId', 'name', 'slug', 'address', 'shortAddress', 'latitude', 'longitude'
	);
	
	public static $primary = 'venueId';
	public static $table = 'venue';
	
	public $venueId;
	public $name;
	public $slug;
	public $address;
	public $shortAddress;
	
	// latitude and longitude deliberately omitted for acceptance testing
}
