<?php
namespace Amiss\Mapper;

use Amiss\Exception;
use Amiss\Meta;

class Chain implements \Amiss\Mapper
{
    private $cache;
    private $internalMetaCache = [];
    private $mappers;
    private $index = [];

    public function __construct(array $mappers, $cache=null)
    {
        parent::__construct();
        $this->cache = $cache;
        $this->mappers = $mappers;
    }

    public function getMeta($class, $strict=true)
    {
        $key = 'chain-class-'.$class;
        if (isset($this->internalMetaCache[$key])) {
            $meta = $this->internalMetaCache[$key] ?: null;
        }
        elseif ($this->cache) {
            $meta = $this->internalMetaCache[$key] = $this->cache->get($key);
        }

        if (!$meta) {
            $meta = $this->findMapper($class)->getMeta($class, $strict);
            if ($this->cache) {
                $this->cache->set($key, $meta);
            }
            $this->internalMetaCache[$key] = $meta ?: false;
        }

        if ($strict && !$meta) {
            throw new \RuntimeException("No metadata for class $class");
        }

        return $meta;
    }

    function mapsClass($class)
    {
        return $this->getMeta($class) == true;
    }

    private function findMapper($input, $strict=true)
    {
        // input to class will be tricky:
        // - array mapper can give arbitrary keys to mapping names with different classes
        // - who do we trust when the $input is a Meta? what about when we are
        //   using a completely custom meta to operate on a similar object 
        //   (i.e. $manager->deleteObject($obj, $otherMeta) )
        throw new \Exception();

        if (isset($this->index[$class])) {
            return $this->index[$class];
        }
        foreach ($this->mappers as $mapper) {
            if ($mapper->mapsClass($class)) {
                return $this->index[$class] = $mapper;
            }
        }
        if ($strict) {
            throw new \RuntimeException("No mapper for class $class");
        }
    }

    function mapRowToProperties($input, $meta=null, $fieldMap=null)
    {
        return $this->findMapper($input)->mapRowToProperties($input, $meta, $fieldMap);
    }

    function mapPropertiesToRow($input, $meta=null)
    {
        return $this->findMapper($input)->mapPropertiesToRow($input, $meta);
    }

    function determineTypeHandler($type)
    {
        // dunno. probably need class/meta as first param
        throw new \BadMethodCallException();
    }

    function mapObjectToRow($input, $meta=null, $context=null)
    {
        return $this->findMapper($input)->mapObjectToRow($input, $meta, $context);
    }

    function mapRowToObject($input, $args=null, $meta=null)
    {
        return $this->findMapper($input)->mapRowToObject($input, $args, $meta);
    }

    function mapObjectsToProperties($objects, $meta=null)
    {
        return $this->findMapper($objects)->mapObjectsToProperties($objects, $meta);
    }

    function mapObjectToProperties($object, $meta=null)
    {
        return $this->findMapper($object)->mapObjectToProperties($object, $meta);
    }

    function formatParams(Meta $meta, $propertyParamMap, $params)
    {
        return $this->findMapper($meta)->mapObjectToProperties($meta, $propertyParamMap, $params);
    }

    function mapRowsToObjects($input, $args=null, $meta=null)
    {
        return $this->findMapper($input)->mapRowsToObjects($input, $args, $meta);
    }

    function mapObjectsToRows($input, $meta=null, $context=null)
    {
        return $this->findMapper($input)->mapObjectsToRows($input, $meta, $context);
    }

    function createObject($meta, $mapped, $args=null)
    {
        return $this->findMapper($meta)->createObject($meta, $mapped, $args);
    }

    function populateObject($object, \stdClass $mapped, $meta=null)
    {
        return $this->findMapper($object)->populateObject($object, $mapped, $meta);
    }
}
