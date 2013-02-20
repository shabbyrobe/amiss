<?php
namespace Amiss\Sql\Type;

class Date implements \Amiss\Type\Handler
{
    public $format;
    public $appTimeZone;
    public $dbTimeZone;
    
    public function __construct($format='datetime', $dbTimeZone, $appTimeZone=null)
    {
        // compat. remove for v4
        if ($format === true)
            $format = 'datetime';
        elseif ($format === false)
            $format = 'date';
        
        if ($format == 'datetime')
            $this->format = 'Y-m-d H:i:s';
        elseif ($format == 'date')
            $this->format = 'Y-m-d';
        else
            $this->format = $format;
        
        if ($this->format == 'U' && !$timeZone)
            $timeZone = 'UTC';
        
        if ($appTimeZone && is_string($appTimeZone))
            $appTimeZone = new \DateTimeZone($appTimeZone);
        if ($dbTimeZone && is_string($dbTimeZone))
            $dbTimeZone = new \DateTimeZone($dbTimeZone);
        
        $this->appTimeZone = $appTimeZone ?: new \DateTimeZone(date_default_timezone_get());
        $this->dbTimeZone = $dbTimeZone;
    }
    
    function prepareValueForDb($value, $object, array $fieldInfo)
    {
        $out = null;
        if ($value instanceof \DateTime) {
            if ($this->timeZone && $value->getTimezone() != $this->timeZone) {
                $value->setTimezone($this->timeZone);
            }
            $out = $value->format($this->format);
        }
        return $out;
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
        $out = null;
        if ($value !== null) {
            $out = \DateTime::createFromFormat($this->format, $value, new \DateTimeZone('UTC'));
            $out->setTimeZone($this->timeZone);
        }
        return $out;
    }
    
    function createColumnType($engine)
    {
        if ($this->format == 'Y-m-d H:i:s')
            return 'datetime';
        elseif ($this->format == 'Y-m-d')
            return 'date';
        elseif ($this->format == 'U')
            return 'int';
        else {
            return $engine == 'sqlite' ? 'STRING' : 'VARCHAR(32)';
        }
    }
}
