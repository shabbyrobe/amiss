<?php
namespace Amiss\Sql\Type;

use Litipk\BigNumbers;

class Decimal implements \Amiss\Type\Handler
{
    public $defaultPrecision;
    public $defaultScale;
    public $zeroIfNull;

    public function __construct($defaultPrecision=null, $defaultScale=null, $zeroIfNull=false)
    {
        $this->defaultPrecision = $defaultPrecision;
        $this->defaultScale = $defaultScale;
        $this->zeroIfNull = $zeroIfNull;
    }

    function prepareValueForDb($value, array $fieldInfo)
    {
        if ($value === null) {
            return $this->zeroIfNull ? 0 : null;
        }
        if (!$value instanceof BigNumbers\Decimal) {
            throw new \UnexpectedValueException();
        }
        if (isset($fieldInfo['scale'])) {
            $value = $value->round($fieldInfo['scale']);
        }
        $precision = isset($fieldInfo['precision']) ? $fieldInfo['precision'] : $this->defaultPrecision;
        return $value->__toString();
    }

    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        if ($value === null && $this->zeroIfNull) {
            $value = "0";
        }
        if ($value !== null) {
            return BigNumbers\Decimal::fromString($value);
        }
    }
    
    function createColumnType($engine, array $fieldInfo)
    {
        $precision = isset($fieldInfo['precision']) ? (int)$fieldInfo['precision'] : $this->defaultPrecision;
        $scale = isset($fieldInfo['scale']) ? (int)$fieldInfo['scale'] : $this->defaultScale;

        $out = "DECIMAL";
        if ($precision || $scale) {
            $out .= "(".($precision ? $precision : "0").",".($scale ? $scale : "0").")";
        }
        return $out;
    }
}
