<?php
namespace Amiss\Sql\Type;

class Date implements \Amiss\Type\Handler
{
    public $formats;
    public $appTimeZone;
    public $dbTimeZone;
    public $forceTime;
    public $classes;

    private $mainClass;

    public function __construct(array $options=[])
    {
        $defaults = [
            'formats'=>'datetime',
            'dbTimeZone'=>null,
            'appTimeZone'=>null,
            'forceTime'=>null,
            'classes'=>[\DateTime::class, \DateTimeImmutable::class],
        ];

        $options = array_merge($defaults, $options);
        if ($diff = array_diff_key($options, $defaults)) {
            throw new \InvalidArgumentException("Unknown keys: ".implode(', ', array_keys($diff)));
        }
 
        switch ($formats = $options['formats']) {
            case 'datetime':
                $this->formats = ['Y-m-d H:i:s', 'Y-m-d H:i'];
            break;
            case 'date':
                $this->formats = ['Y-m-d'];
            break;
            default:
                $this->formats = is_array($formats) ? $formats : [$formats];
        }

        $appTimeZone = $options['appTimeZone'];
        if ($appTimeZone && is_string($appTimeZone)) {
            $appTimeZone = new \DateTimeZone($appTimeZone);
        }

        $dbTimeZone = $options['dbTimeZone'];
        if ($dbTimeZone && is_string($dbTimeZone)) {
            $dbTimeZone = new \DateTimeZone($dbTimeZone);
        }
        
        $this->dbTimeZone  = $dbTimeZone;
        $this->appTimeZone = $appTimeZone ?: $dbTimeZone;
        $this->forceTime   = $options['forceTime'];

        if ($this->forceTime && is_string($this->forceTime)) {
            $this->forceTime = explode(':', $this->forceTime);
            if (!isset($this->forceTime[0]) || !isset($this->forceTime[1]) || isset($this->forceTime[3])) {
                throw new \InvalidArgumentException("forceTime must be a 2- or 3-tuple of integers [H, M, S] or a colon-separated string of numbers H:M:S");
            }
        }

        if (!$options['classes']) {
            throw new \InvalidArgumentException("Date classes missing");
        }
        $this->classes = (array) $options['classes'];
        if (!is_array($this->classes)) {
            throw new \InvalidArgumentException("Date classes must be string or array of strings");
        }

        $this->mainClass = $this->classes[0];
        foreach ($this->classes as $class) {
            if (
                !is_a($class, \DateTimeImmutable::class, true) && 
                !is_a($class, \DateTime::class, true)
            ) {
                throw new \InvalidArgumentException("Date class $class does not extend from DateTime or DateTimeImmutable");
            }
            if (!method_exists($class, 'createFromFormat')) {
                throw new \InvalidArgumentException("Date class $class must contain static createFromFormat() method");
            }
        }
        // $this->appTimeZone = $appTimeZone ?: new \DateTimeZone(date_default_timezone_get());
    }
    
    public static function unixTime(array $config=[])
    {
        $config = array_merge($config, ['formats'=>'U', 'dbTimeZone'=>'UTC']);
        return new static($config);
    }
    
    function prepareValueForDb($value, array $fieldInfo)
    {
        $out = null;
        if ($value === null) {
            return $value;
        }

        $ok = false;
        foreach ($this->classes as $c) {
            if ($value instanceof $c) {
                $ok = true;
                break;
            }
        }
        
        if (!$ok) {
            $type = gettype($value);
            $classes = implode(', ', $this->classes);
            throw new \UnexpectedValueException(
                "Date value was invalid. Expected {$classes}, found ".
                ($type == 'object' ? get_class($value) : $type)
            );
        }

        // This conversion may not be an issue. Wait until it is raised
        // before making a decision.
        // also - this doesn't seem to work right as of 5.5.1:
        // var_dump(new DateTimeZone('Australia/Melbourne') == new DateTimeZone('UTC'));
        // bool(true)
        // https://bugs.php.net/bug.php?id=54655
        if ($value->getTimeZone() != $this->appTimeZone) {
            throw new \UnexpectedValueException();
        }
        $value = clone $value;
        $value = $value->setTimeZone($this->dbTimeZone);
        if ($this->forceTime) {
            $value = $value->setTime(...$this->forceTime);
        }
        return $value->format($this->formats[0]);
    }
    
    function handleValueFromDb($value, array $fieldInfo, $row)
    {
        $out = null;
        if ($value !== null && $value !== '' && $value !== false) {
            $dateClass = $this->mainClass;

            foreach ($this->formats as $format) {
                $out = $dateClass::createFromFormat("$format", $value, $this->dbTimeZone);
                if ($out instanceof $dateClass) {
                    $out = $out->setTimeZone($this->appTimeZone);
                    if ($this->forceTime) {
                        $out = $out->setTime(...$this->forceTime);
                    }
                    break;
                }
            }

            if (!$out) {
                throw new \UnexpectedValueException("Date '$value' could not be handled with any of the following formats: ".implode(', ', $this->formats));
            }
        }
        return $out;
    }
    
    function createColumnType($engine, array $fieldInfo)
    {
        if ($this->formats[0] == 'Y-m-d H:i:s') {
            return 'datetime';
        } elseif ($this->formats[0] == 'Y-m-d') {
            return 'date';
        } elseif ($this->formats[0] == 'U') {
            return 'int';
        } else {
            return $engine == 'sqlite' ? 'STRING' : 'VARCHAR(32)';
        }
    }
}
