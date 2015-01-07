<?php
namespace Amiss\Mongo\Type;

class Date implements \Amiss\Type\Handler
{
    public $timeZone;
    
    public function __construct($timeZone=null)
    {
        $this->timeZone = $timeZone;
    }
    
    function prepareValueForDb($value, array $fieldInfo)
    {
        if ($value) {
            if ($this->timeZone && $value->getTimezone() != $this->timeZone) {
                $value->setTimezone($this->timeZone);
            }
            return new \MongoDate($value->getTimestamp(), $value->format('u'));
        }
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        $out = null;
        if ($value) {
            // without the str_pad, php's DateTime will right pad the usec value with zeroes!
            // 21000 becomes 210000. up becomes down. monkeys fly out of your butt!
            $export = $value->sec.' '.str_pad($value->usec, 6, '0', STR_PAD_LEFT);
            $format = 'U u';
            if ($this->timeZone)
                $out = \DateTime::createFromFormat($format, $export, $this->timeZone);
            else
                $out = \DateTime::createFromFormat($format, $export);
        }
        return $out;
    }
    
    function createColumnType($engine)
    {}
}
