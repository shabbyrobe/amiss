<?php
namespace Amiss\Sql;

use Amiss\Exception;
use Amiss\Meta;
use Amiss\Sql\Connector;
use Amiss\Mapper;

abstract class TableBuilder
{
    /**
     * @var Amiss\Meta
     */
    protected $meta;
    
    /**
     * @var Amiss\Mapper
     */
    protected $mapper;
    
    protected $engine;

    protected $defaultFieldType;
    
    public function __construct(Mapper $mapper, $class)
    {
        if (!$this->engine) {
            throw new \UnexpectedValueException();
        }

        $this->mapper = $mapper;
        
        if ($class instanceof Meta) {
            $this->meta = $class;
        } else {
            $this->meta = $mapper->getMeta($class);
        }
    }

    public static function create(Connector $connector, Mapper $mapper, $classes)
    {
        foreach (static::createSQL($connector, $mapper, (array) $classes) as $classQueries) {
            $connector->execAll($classQueries);
        }
    }

    public static function createSQL($engine, Mapper $mapper, $classes)
    {
        if ($engine instanceof Connector) {
            $engine = $engine->engine;
        }

        $single = false;
        if (is_string($classes)) {
            $single = true;
            $classes = [$classes];
        }

        $queries = [];
        foreach ((array) $classes as $class) {
            $builder = null;
            switch ($engine) {
                case 'sqlite': $builder = new Engine\SQLite\TableBuilder($mapper, $class); break;
                case 'mysql' : $builder = new Engine\MySQL\TableBuilder ($mapper, $class); break;
                default:
                    throw new \Exception();
            }
            $queries[$class] = $builder->buildTableQueries();
        }

        if ($single) {
            return current($queries);
        } else {
            return $queries;
        }
    }

    public function getClass()
    {
        return $this->meta->class;
    }
    
    /**
     * @return array
     */
    public abstract function buildTableQueries();

    protected function buildField($id, $info, $default)
    {
        $current = "`{$info['name']}` ";
        
        $colType = null;
        if (isset($info['type'])) {
            $handler = $this->mapper->determineTypeHandler($info['type']['id']);
            if ($handler) {
                $new = $handler->createColumnType($this->engine);
                if ($new) { 
                    $colType = $new;
                }
            }
            if (!$colType) {
                $colType = $info['type']['id'];
            }
        }

        $current .= $colType ?: $default;
        return $current;
    }

    protected function buildFields()
    {
        $fields = $this->meta->getFields();
        if (!$fields) {
            throw new Exception("No fields defined for {$this->meta->class}");
        }

        $primary = $this->meta->primary;
        
        $defaultType = $this->meta->getDefaultFieldType();
        if (!$defaultType) {
            $defaultType = $this->defaultFieldType;
        }

        $f = array();
        $found = array();
        
        // make sure the primary key ends up first
        $pFieldIds = [];
        foreach ($this->meta->primary as $p) {
            $pFieldIds[$p] = true;
        }

        $pFieldOut = [];
        $fieldOut = [];
        foreach ($fields as $id=>$info) {
            $f = $this->buildField($id, $info, $defaultType);
            if (isset($pFieldIds[$id])) {
                $pFieldOut[$id] = $f;
            } else {
                $fieldOut[$id] = $f;
            }
        }
        
        return array_merge($pFieldOut, $fieldOut);
    }    
}
