<?php
namespace Amiss\Demo;

class Artist extends Object
{
    /**
     * @primary
     * @type autoinc
     */
    public $artistId;
    
    /** @field */
    public $artistTypeId;
    
    /** @field */
    public $name;
    
    /** @field */
    public $slug;
    
    /**
     * @field
     * @type LONGTEXT
     */
    public $bio;
    
    /** @has one of=ArtistType; on=artistTypeId */
    public $artistType;
    
    /** @has assoc of=Event; via=EventArtist */
    public $events;
}

class ArtistType extends Object
{
    /**
     * @primary
     * @type autoinc
     */
    public $artistTypeId;
    
    /** @field */
    public $type;
    
    /** @field */
    public $slug;
    
    /** @has many of=Artist */
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
    
    /** @field */
    public $dateStart;
    
    /** @field */
    public $dateEnd;
    
    /** @field */
    public $venueId;
    
    /** @has many of=EventArtist */
    public $eventArtists;
    
    /** @has one of=Venue; on=venueId */
    public $venue;
    
    /** @has assoc of=Artist; via=EventArtist */
    public $artists;
    
    /** @field */
    public function getSlug()
    {
        return $this->slug;
    }
    
    public function setSlug($value)
    {
        $this->slug = $value;
    }
    
    /** @field */
    public function getName()
    {
        return $this->name;
    }
    
    public function setName($value)
    {
        $this->name = $value;
        if (!$this->slug) {
            $this->slug = trim(
                preg_replace('/-+/', '-', preg_replace('/[^a-z0-9\-]/', '', 
                preg_replace('/\s+/', '-', strtolower($value)))), '-'
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
    /** @primary */
    public $eventId;
    
    /** @primary */
    public $artistId;
    
    /** @field */
    public $priority;
    
    /** @field */
    public $sequence;
    
    /** @field */
    public $eventArtistName;
    
    /**
     * @has one of=Event; on=eventId
     * @var Amiss\Demo\Event
     */
    public $event;
    
    /**
     * @has one of=Artist; on=artistId
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
    
    /** @field name */
    public $venueName;
    
    /** @field slug */
    public $venueSlug;
    
    /** @field address */
    public $venueAddress;
    
    /** @field shortAddress */
    public $venueShortAddress;
    
    /** @field latitude */
    public $venueLatitude;
    
    /** @field longitude */
    public $venueLongitude;
}
