<?php
namespace Amiss\Demo;

class Artist extends Object
{
    /**
     * @primary
     * @type autoinc
     */
    public $artistId;
    
    /**
     * @field
     * @index
     */
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
    
    /** 
     * @has.one.of ArtistType
     * @has.one.from artistTypeId
     */
    public $artistType;
    
    /**
     * @has.assoc.of Event
     * @has.assoc.via EventArtist
     */
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
    
    /**
     * @field
     * @readOnly
     */
    public function getSlug()
    {
        return trim(preg_replace('/[^a-z\d]+/', '-', strtolower($this->type)), '-');
    }
    
    /**
     * @has.many.of Artist
     * @has.many.inverse artistType
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
    
    /** @field */
    public $dateStart;
    
    /** @field */
    public $dateEnd;
    
    /**
     * @field
     * @index
     */
    public $venueId;
    
    /**
     * @has.many.of EventArtist
     * @has.many.inverse event
     */
    public $eventArtists;

    /**
     * @has.many.of Ticket
     * @has.many.inverse event
     */
    public $tickets;
    
    /**
     * @has.one.of Venue
     * @has.one.from venueId
     */
    public $venue;
    
    /** 
     * @has.assoc.of Artist
     * @has.assoc.via EventArtist
     */
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

class Ticket
{
    /** @primary */
    public $ticketId;

    /**
     * @field
     * @index
     */
    public $eventId;

    /** @field */
    public $name;

    /** @field */
    public $cost;

    /** @field */
    public $numAvailable;

    /** @field */
    public $numSold;

    /**
     * @has.one.of Event
     * @has.one.from eventId
     */
    public $event;
}

class EventArtist
{
    /** @primary */
    public $eventId;
    
    /**
     * @primary
     * @index
     */
    public $artistId;
    
    /** @field */
    public $priority;
    
    /** @field */
    public $sequence;
    
    /** @field */
    public $eventArtistName;
    
    /**
     * @has.one.of Event
     * @var Amiss\Demo\Event
     */
    public $event;
    
    /**
     * @has.one.of Artist
     * @has.one.from artistId
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
