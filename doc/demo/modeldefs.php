<?php
namespace Amiss\Demo;

class Artist extends Object
{
    /** :amiss = {"field": {"primary": true, "type": "autoinc"}}; */
    public $artistId;
    
    /** :amiss = {"field": {"index": true}}; */
    public $artistTypeId;
    
    /** :amiss = {"field": true}; */
    public $name;
    
    /** :amiss = {"field": {"index": {"key": true}}}; */
    public $slug;
    
    /** :amiss = {"field": {"type": "LONGTEXT"}}; */
    public $bio;
    
    /**
     * :amiss = {"has": {
     *     "type": "one",
     *     "of"  : "ArtistType",
     *     "from": "artistTypeId"
     * }};
     */
    public $artistType;
    
    /**
     * :amiss = {"has": {
     *     "type": "assoc",
     *     "of"  : "Event",
     *     "via" : "EventArtist"
     * }};
     */
    public $events;
}

class ArtistType extends Object
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $artistTypeId;
    
    /** :amiss = {"field": true}; */
    public $type;
    
    /** :amiss = {"field": {"readOnly": true}}; */
    public function getSlug()
    {
        return trim(preg_replace('/[^a-z\d]+/', '-', strtolower($this->type)), '-');
    }
    
    /**
     * :amiss = {"has": {
     *     "type"   : "many",
     *     "of"     : "Artist",
     *     "inverse": "artistType"
     * }};
     */
    public $artists = array();
}

class Event extends Object
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $eventId;
    
    private $name;
    
    private $subName;
    
    private $slug;

    /** :amiss = {"field": {"type": "datetime"}}; */
    public $dateStart;

    /** :amiss = {"field": {"type": "datetime"}}; */
    public $dateEnd;
    
    /** :amiss = {"field": {"index": true}}; */
    public $venueId;
    
    /**
     * :amiss = {"has": {
     *     "type"   : "many",
     *     "of"     : "EventArtist",
     *     "inverse": "event"
     * }};
     */
    public $eventArtists;

    /**
     * :amiss = {"has": {"type": "many", "of": "Ticket"}};
     */
    public $tickets;
    
    /**
     * :amiss = {"has": {"type": "one", "of": "Venue", "from": "venueId"}};
     */
    public $venue;
    
    /** 
     * :amiss = {"has": {
     *     "type": "assoc",
     *     "of"  : "Artist",
     *     "via" : "EventArtist"
     * }};
     */
    public $artists;
    
    /** :amiss = {"field": true}; */
    public function getSlug()
    {
        return $this->slug;
    }
    
    public function setSlug($value)
    {
        $this->slug = $value;
    }

    /** :amiss = {"field": true}; */
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
     * :amiss = {"field": {"name": "sub_name", "setter": "setTheSubName"}};
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

/**
 * :amiss = {
 *     "relations": {
 *         "event": {"type": "one", "of"  : "Event", "from": "eventId"}
 *     }
 * };
 */
class Ticket
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $ticketId;

    /** :amiss = {"field": { "index": true }}; */
    public $eventId;

    /** :amiss = {"field": true}; */
    public $name;

    /** :amiss = {"field": true}; */
    public $cost;

    /** :amiss = {"field": true}; */
    public $numAvailable;

    /** :amiss = {"field": true}; */
    public $numSold;
}

class PlannedEvent extends Event
{
    /** :amiss = {"field": {"type": "tinyint"}}; */
    public $completeness;
}

class EventArtist
{
    /** :amiss = {"field": { "primary": true }}; */
    public $eventId;
    
    /** :amiss = {"field": { "primary": true, "index": true }}; */
    public $artistId;
    
    /** :amiss = {"field": true}; */
    public $priority;
    
    /** :amiss = {"field": true}; */
    public $sequence;
    
    /** :amiss = {"field": true}; */
    public $eventArtistName;
    
    /**
     * :amiss = {"has": {
     *     "type": "one",
     *     "of"  : "Event"
     * }};
     * @var Amiss\Demo\Event
     */
    public $event;
    
    /**
     * :amiss = {"has": {
     *     "type": "one",
     *     "of"  : "Artist",
     *     "from": "artistId"
     * }};
     * @var Amiss\Demo\Artist
     */
    public $artist;
}

class Venue extends Object
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }}; */
    public $venueId;
    
    /** :amiss = {"field": "name"}; */
    public $venueName;
    
    /** :amiss = {"field": "slug"}; */
    public $venueSlug;
    
    /** :amiss = {"field": "address"}; */
    public $venueAddress;
    
    /** :amiss = {"field": "shortAddress"}; */
    public $venueShortAddress;
    
    /** :amiss = {"field": "latitude"}; */
    public $venueLatitude;
    
    /** :amiss = {"field": "longitude"}; */
    public $venueLongitude;
}
