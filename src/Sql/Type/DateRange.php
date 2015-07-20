<?php
namespace Amiss\Sql\Type;

class DateRange extends \Amiss\Sql\Type\Date
{
    const DIR_LOWER = 1;
    const DIR_UPPER = 2;

    public $sentinel;
    public $type;

    static $types = [self::DIR_LOWER, self::DIR_UPPER];

    // don't add anything to this that doesn't have 59:59 at the end of the upper.
    // it interacts badly with forceTime.
    // if an rdbms has a sentinel date of 10000-01-01 00:00:00, set it to 9999-12-31 23:59:59 instead.
    static $engineSentinel = [
        // https://dev.mysql.com/doc/refman/5.0/en/datetime.html
        'mysql'  => ['1000-01-01 00:00:00', '9999-12-31 23:59:59'],

        // https://www.sqlite.org/lang_datefunc.html
        'sqlite' => ['0000-01-01 00:00:00', '9999-12-31 23:59:59'],
    ];

    static function createPair($engine, $config)
    {
        if (!isset(static::$engineSentinel[$engine])) {
            throw new \InvalidArgumentException();
        }
        
        list ($lower, $upper) = static::$engineSentinel[$engine];

        $lowerConfig = $config;
        $lowerConfig['sentinel'] = $lower;
        $lowerConfig['type'] = self::DIR_LOWER;

        $upperConfig = $config;
        $upperConfig['sentinel'] = $upper;
        $upperConfig['type'] = self::DIR_UPPER;

        return [new static($lowerConfig), new static($upperConfig)];
    }

    public function __construct($config)
    {
        if (!isset($config['type'])) {
            throw new \InvalidArgumentException();
        }
        if (!isset($config['sentinel'])) {
            throw new \InvalidArgumentException();
        }
        $sentinel = array_find_unset($config, 'sentinel');
        $this->type = array_find_unset($config, 'type');
        if (!in_array($this->type, static::$types)) {
            throw new \Exception();
        }
        parent::__construct($config);

        $this->sentinel = \DateTime::createFromFormat('Y-m-d|', $sentinel, $this->dbTimeZone);
    }

    function prepareValueForDb($value, array $fieldInfo)
    {
        if ($value == null) {
            $value = $this->sentinel;
        }
        return parent::prepareValueForDb($value, $fieldInfo);
    }

    protected function prepareDateTime(\DateTime $value)
    {
        $value = parent::prepareDateTime($value);

        // this could present an issue if the sentinel date for a dbms has a time of 00:00
        // instead of 59:59 - prepareDateTime applies forceTime, which may be anything.
        if ($this->type == self::DIR_LOWER && $value < $this->sentinel) {
            throw new \Exception("Encountered input value lower than sentinel!");
        }
        elseif ($this->type == self::DIR_UPPER && $value > $this->sentinel) {
            throw new \Exception("Encountered input value higher than sentinel!");
        }
        return $value;
    }

    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        $handled = parent::handleValueFromDb($value, $fieldInfo, $row);
        $isInstance = false;
        foreach ($this->classes as $c) {
            if ($value instanceof $c) {
                $isInstance = true;
                break;
            }
        }
        if ($isInstance && $handled == $this->sentinel) {
            $handled = null;
        }
        return $handled;
    }
}
