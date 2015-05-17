<?php
namespace Amiss\Demo\Active;

/**
 * :amiss = {"table": "artist"}
 */
class ArtistRecord extends \Amiss\Sql\ActiveRecord
{
    /** :amiss = {"field": {"primary": true, "type": "autoinc"}} */
    public $artistId;
    
    /** :amiss = {"field": {"index": true}} */
    public $artistTypeId;
    
    /** :amiss = {"field": true} */
    public $name;
    
    /** :amiss = {"field": {"index": {"key": true}}} */
    public $slug;
    
    /** :amiss = {"field": {"type": "LONGTEXT"}} */
    public $bio;

    private $type;
    
    /**
     * :amiss = {"has": {
     *     "type": "assoc",
     *     "of"  : "Event",
     *     "via" : "EventArtist"
     * }}
     */
    public function getType()
    {
        if ($this->type === null) {
            $this->type = $this->getRelated('type');
        }
        return $this->type;
    }
}

class ArtistType extends \Amiss\Sql\ActiveRecord
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }} */
    public $artistTypeId;
    
    /** :amiss = {"field": true} */
    public $type;
    
    /** :amiss = {"field": {"readOnly": true}} */
    public function getSlug()
    {
        return trim(preg_replace('/[^a-z\d]+/', '-', strtolower($this->type)), '-');
    }
    
    private $artists = null;
    
    /**
     * :amiss = {"has": {
     *     "type": "many",
     *     "of"  : "Artist",
     *     "to"  : "artistTypeId"
     * }}
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
 * :amiss = {"table": "event"}
 */
class EventRecord extends \Amiss\Sql\ActiveRecord
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }} */
    public $eventId;
    
    /** :amiss = {"field": {"type": "datetime"}} */
    public $dateStart;
    
    /** :amiss = {"field": {"type": "datetime"}} */
    public $dateEnd;
    
    /** :amiss = {"field": {"index": true}} */
    public $venueId;
    
    /** :amiss = {"field": true} */
    public $name;
    
    /** :amiss = {"field": "sub_name"} */
    public $subName;
    
    /** :amiss = {"field": {"index": {"key": true}}} */
    public $slug;
    
    private $eventArtists;
    
    /**
     * @var Amiss\Demo\Active\VenueRecord
     */
    private $venue;
    
    /**
     * :amiss = {"has": {"type": "one", "of": "VenueRecord", "from": "venueId"}}
     */
    public function getVenue()
    {
        if (!$this->venue && $this->venueId) {
            $this->venue = $this->getRelated('venue');
        }
        return $this->venue;
    }
    
    /**
     * :amiss = {"has": {
     *     "type"   : "many",
     *     "of"     : "EventArtist",
     *     "inverse": "event"
     * }}
     */
    public function getEventArtists()
    {
        if (!$this->eventArtists) {
             $this->eventArtists = $this->getRelated('eventArtists');
        }
        return $this->eventArtists;
    }
}

/**
 * :amiss = {
 *     "relations": {
 *         "event": {"type": "one", "of"  : "Event", "from": "eventId"}
 *     }
 * }
 */
class Ticket extends \Amiss\Sql\ActiveRecord
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }} */
    public $ticketId;

    /** :amiss = {"field": { "index": true }} */
    public $eventId;

    /** :amiss = {"field": true} */
    public $name;

    /** :amiss = {"field": true} */
    public $cost;

    /** :amiss = {"field": true} */
    public $numAvailable;

    /** :amiss = {"field": true} */
    public $numSold;
}

class PlannedEvent extends EventRecord
{
    /** :amiss = {"field": {"type": "tinyint"}} */
    public $completeness;
}

class EventArtist extends \Amiss\Sql\ActiveRecord
{
    /** :amiss = {"field": { "primary": true }} */
    public $eventId;
    
    /** :amiss = {"field": { "primary": true, "index": true }} */
    public $artistId;
    
    /** :amiss = {"field": true} */
    public $priority;
    
    /** :amiss = {"field": true} */
    public $sequence;
    
    /** :amiss = {"field": true} */
    public $eventArtistName;
    
    /**
     * :amiss = {"has": {
     *     "type": "one",
     *     "of"  : "Event"
     * }}
     * @var Amiss\Demo\Event
     */
    public $event;
    
    /**
     * :amiss = {"has": {
     *     "type": "one",
     *     "of"  : "Artist",
     *     "from": "artistId"
     * }}
     * @var Amiss\Demo\Artist
     */
    public $artist;
}

/**
 * :amiss = {"table": "venue"}
 */
class VenueRecord extends \Amiss\Sql\ActiveRecord
{
    /** :amiss = {"field": { "primary": true, "type": "autoinc" }} */
    public $venueId;
    
    /** :amiss = {"field": "name"} */
    public $venueName;
    
    /** :amiss = {"field": "slug"} */
    public $venueSlug;
    
    /** :amiss = {"field": "address"} */
    public $venueAddress;
    
    /** :amiss = {"field": "shortAddress"} */
    public $venueShortAddress;
    
    /** :amiss = {"field": "latitude"} */
    public $venueLatitude;
    
    /** :amiss = {"field": "longitude"} */
    public $venueLongitude;
}
