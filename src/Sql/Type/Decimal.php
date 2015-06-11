<?php
namespace Amiss\Sql\Type;

use Litipk\BigNumbers;

class Decimal implements \Amiss\Type\Handler
{
    public $defaultPrecision;
    public $defaultScale;

    public function __construct($defaultPrecision=null, $defaultScale=null)
    {
        $this->defaultPrecision = $defaultPrecision;
        $this->defaultScale = $defaultScale;
    }

    function prepareValueForDb($value, array $fieldInfo)
    {
        if (isset($fieldInfo['scale'])) {
            $value = $value->round($fieldInfo['scale']);
        }
        $precision = isset($fieldInfo['precision']) ? $fieldInfo['precision'] : $this->defaultPrecision;
        return $value->__toString();
    }

    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        return BigNumbers\Decimal::fromString($value);
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
