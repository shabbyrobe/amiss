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
        $this->cache = $cache;
        $this->mappers = $mappers;
    }

    public function getMeta($id, $strict=true)
    {
        $meta = null;
        if (!is_string($id)) {
            throw new \InvalidArgumentException();
        }

        $key = 'chain-id-'.$id;
        if (isset($this->internalMetaCache[$key])) {
            $meta = $this->internalMetaCache[$key] ?: null;
        }
        elseif ($this->cache) {
            $meta = $this->internalMetaCache[$key] = $this->cache->get($key);
        }

        if (!$meta) {
            $meta = $this->findMapper($id)->getMeta($id, $strict);
            if ($this->cache) {
                $this->cache->set($key, $meta);
            }
            $this->internalMetaCache[$key] = $meta ?: false;
        }

        if ($strict && !$meta) {
            throw new \RuntimeException("No metadata for id $id");
        }

        return $meta;
    }

    function canMap($id)
    {
        return $this->getMeta($id) == true;
    }

    private function findMapper($id, $strict=true)
    {
        if ($id instanceof Meta) {
            $id = $id->id;
        }
        if (isset($this->index[$id])) {
            return $this->index[$id];
        }

        foreach ($this->mappers as $mapper) {
            if ($mapper->canMap($id)) {
                return $this->index[$id] = $mapper;
            }
        }
        if ($strict) {
            throw new \RuntimeException("No mapper for id $id");
        }
    }

    function mapRowToProperties($meta, $row, $fieldMap=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta);
        }

        return $this->findMapper($meta)->mapRowToProperties($meta, $row, $fieldMap);
    }

    function mapPropertiesToRow($meta, $properties)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta);
        }

        return $this->findMapper($meta)->mapPropertiesToRow($meta, $properties);
    }

    function determineTypeHandler($type)
    {
        // dunno. probably need class/meta as first param
        throw new \BadMethodCallException();
    }

    function mapObjectToRow($object, $meta=null, $context=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: get_class($object));
        }

        return $this->findMapper($meta)->mapObjectToRow($object, $meta, $context);
    }

    function mapRowToObject($meta, $row, $args=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta);
        }

        return $this->findMapper($meta)->mapRowToObject($meta, $row, $args);
    }

    function mapObjectsToProperties($objects, $meta=null)
    {
        if (!$objects) {
            return [];
        }
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: get_class(current($objects)));
        }
        return $this->findMapper($meta)->mapObjectsToProperties($objects, $meta);
    }

    function mapObjectToProperties($object, $meta=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: get_class($object));
        }
        return $this->findMapper($meta)->mapObjectToProperties($object, $meta);
    }

    function formatParams(Meta $meta, $propertyParamMap, $params)
    {
        return $this->findMapper($meta)->formatParams($meta, $propertyParamMap, $params);
    }

    function mapRowsToObjects($meta, $rows, $args=null)
    {
        if (!$meta instanceof Meta) { $meta = $this->getMeta($meta); }

        return $this->findMapper($meta)->mapRowsToObjects($meta, $rows, $args);
    }

    function mapObjectsToRows($objects, $meta=null, $context=null)
    {
        if (!$objects) {
            return [];
        }
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: get_class(current($objects)));
        }
        return $this->findMapper($meta)->mapObjectsToRows($objects, $meta, $context);
    }

    function createObject($meta, $mapped, $args=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta);
        }
        return $this->findMapper($meta)->createObject($meta, $mapped, $args);
    }

    function populateObject($object, \stdClass $mapped, $meta=null)
    {
        if (!$meta instanceof Meta) {
            $meta = $this->getMeta($meta ?: get_class($object));
        }
        return $this->findMapper($meta)->populateObject($object, $mapped, $meta);
    }
}
