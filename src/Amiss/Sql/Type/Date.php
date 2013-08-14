<?php
namespace Amiss\Sql\Type;

class Date implements \Amiss\Type\Handler
{
    public $formats;
    public $appTimeZone;
    public $dbTimeZone;
    
    public function __construct($formats='datetime', $dbTimeZone, $appTimeZone=null)
    {
        // compat. remove for v5
        if ($formats === true)
            $formats = 'datetime';
        elseif ($formats === false)
            $formats = 'date';
        
        if ($formats == 'datetime')
            $this->formats = array('Y-m-d H:i:s', 'Y-m-d', 'Y-m-d H:i:s');
        elseif ($formats == 'date')
            $this->formats = array('Y-m-d');
        else
            $this->formats = is_array($formats) ? $formats : array($formats);
        
        if ($appTimeZone && is_string($appTimeZone))
            $appTimeZone = new \DateTimeZone($appTimeZone);
        if ($dbTimeZone && is_string($dbTimeZone))
            $dbTimeZone = new \DateTimeZone($dbTimeZone);
        
        $this->dbTimeZone = $dbTimeZone;
        $this->appTimeZone = $appTimeZone ?: $dbTimeZone;
        // $this->appTimeZone = $appTimeZone ?: new \DateTimeZone(date_default_timezone_get());
    }
    
    public static function unixTime($appTimeZone=null)
    {
        return new static('U', 'UTC', $appTimeZone);
    }
    
    function prepareValueForDb($value, $object, array $fieldInfo)
    {
        $out = null;
        if ($value instanceof \DateTime) {
            // This conversion may not be an issue. Wait until it is raised
            // before making a decision.
            // also - this doesn't seem to work right as of 5.5.1:
            // var_dump(new DateTimeZone('Australia/Melbourne') == new DateTimeZone('UTC'));
            // bool(true)
            // https://bugs.php.net/bug.php?id=54655
            if ($value->getTimeZone() != $this->appTimeZone)
                throw new \UnexpectedValueException();
            
            $value->setTimeZone($this->dbTimeZone);
            $out = $value->format($this->formats[0]);
        }
        return $out;
    }
    
    function handleValueFromDb($value, $object, array $fieldInfo, $row)
    {
        $out = null;
        if ($value !== null && $value !== '' && $value !== false) {
            foreach ($this->formats as $format) {
                $out = \DateTime::createFromFormat($format, $value, $this->dbTimeZone);
                if ($out instanceof \DateTime) {
                    $out->setTimeZone($this->appTimeZone);
                    break;
                }
            }
            if (!$out) {
                throw new \UnexpectedValueException("Date '$value' could not be handled with any of the following formats: ".implode(', ', $this->formats));
            }
        }
        return $out;
    }
    
    function createColumnType($engine)
    {
        if ($this->formats[0] == 'Y-m-d H:i:s')
            return 'datetime';
        elseif ($this->formats[0] == 'Y-m-d')
            return 'date';
        elseif ($this->formats[0] == 'U')
            return 'int';
        else {
            return $engine == 'sqlite' ? 'STRING' : 'VARCHAR(32)';
        }
    }
}
