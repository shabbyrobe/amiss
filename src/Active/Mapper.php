<?php

namespace Amiss\Active;

class Mapper extends \Amiss\Mapper
{
	function getMeta($class)
	{
		$info = array();
		
		$rc = new \ReflectionClass($class);
		
		$statics = $rc->getStaticProperties();
		
		$table = isset($statics['table']) ? $statics['table'] : $this->getDefaultTable($class);
		$info = array(
			'fields'=>array(),
			'relations'=>isset($statics['relations']) ? $statics['relations'] : array(), 
		);
		
		if (isset($statics['defaultFieldType']))
			 $info['defaultFieldType'] = $statics['defaultFieldType'];
		if (isset($statics['primary']))
			 $info['primary'] = $statics['primary'];
		
		if (isset($statics['fields'])) {
			foreach ($statics['fields'] as $k=>$v) {
				// this micro-optimisation saves us an is_numeric call. 
				// php converts array key string('0') into int(0) and string('0')==0
				if ($k == 0 && $k !== 0)
					$info['fields'][$k] = array('name'=>$k, 'type'=>$v);
				else
					$info['fields'][$v] = array('name'=>$v, 'type'=>null);
			}
		}
		else {
			foreach ($rc->getProperties(\ReflectionProperty::IS_PUBLIC) as $prop) {
				if ($prop->class == $class) {
					if (!isset($info['relations'][$prop->name])) {
						$info['fields'][$prop->name] = array('name'=>$prop->name, 'type'=>null);
					}
				}	
			}
		}
		
		if (isset($info['primary']) && !isset($info['fields'][$info['primary']])) {
			$info['fields'][$info['primary']] = array(
				'name'=>$info['primary'], 
				'type'=>'INT PRIMARY KEY AUTO_INCREMENT'
			);
		}
		
		$parentClass = get_parent_class($class);
		$parent = null;
		if ($parentClass && $parentClass != 'Amiss\Active\Record') {
			$parent = $this->getMeta($parentClass);
		}
		
		$meta = new \Amiss\Meta($class, $table, $info, $parent);
		
		return $meta; 
	}
	
	function createObject($meta, $row, $args)
	{
		$object = parent::createObject($meta, $row, $args);
		$object->setFetched();
		return $object;
	}
}
