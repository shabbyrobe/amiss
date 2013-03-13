<?php
namespace Amiss\Sql;

use Amiss\Exception,
    Amiss\Sql\Connector
;

class TableBuilder
{
    /**
     * @var Amiss\Meta
     */
    private $meta;
    
    /**
     * @var Amiss\Sql\Manager
     */
    private $manager;
    
    private $class;
    
    public function __construct($manager, $class)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->meta = $manager->getMeta($class);
    }
    
    public function getClass()
    {
        return $this->meta->class;
    }
    
    public function createTable()
    {
        $connector = $this->manager->getConnector();
        
        if (!($connector instanceof Connector))
            throw new Exception("Can't create tables if not using Amiss\Sql\Connector");
        
        $sql = $this->buildCreateTableSql();
        
        $connector->exec($sql);
    }
    
    protected function buildFields()
    {
        $engine = $this->manager->getConnector()->engine;
        $primary = $this->meta->primary;
        
        $default = $this->meta->getDefaultFieldType();
        if (!$default) {
            $default = array('id'=>$engine == 'sqlite' ? 'STRING NULL' : 'VARCHAR(255) NULL');
        } 
        $f = array();
        $found = array();
        
        $fields = $this->meta->getFields();
        
        // make sure the primary key ends up first
        if ($this->meta->primary) {
            $pFields = array();
            foreach ($this->meta->primary as $p) {
                $primaryField = $fields[$p];
                unset($fields[$p]);
                $pFields[$p] = $primaryField;
            }
            $fields = array_merge($pFields, $fields);
        }
        
        foreach ($fields as $id=>$info) {
            $current = "`{$info['name']}` ";
            
            $type = isset($info['type']) ? $info['type'] : $default;
            $typeId = $type['id'];
            
            $handler = $this->manager->mapper->determineTypeHandler($typeId);
            if ($handler) {
                $new = $handler->createColumnType($engine);
                if ($new) $typeId = $new;
            }
            
            $current .= $typeId;
            $f[] = $current;
            $found[] = $id;
        }
        
        return $f;
    }
    
    protected function buildTableConstraints()
    {
        $engine = $this->manager->getConnector()->engine;
        
        $fields = $this->meta->getFields();
        
        $idx = array();
        if ($engine == 'mysql') {
            foreach ($this->meta->relations as $k=>$details) {
                if ($details[0] == 'one' || $details[0] == 'many') {
                    $relatedMeta = $this->manager->getMeta($details['of']);
                    
                    $cols = array();
                    if (isset($details['inverse'])) {
                        $inverse = $relatedMeta->relations[$details['inverse']];
                        $details['on'] = $inverse['on'];
                        if (is_array($details['on']))
                            $details['on'] = array_combine(array_values($inverse['on']), array_keys($inverse['on']));
                    }
                    
                    if (is_string($details['on'])) {
                        $cols[] = $details['on'];
                    }
                    elseif ($details['on']) {
                        foreach ($details['on'] as $l=>$r) {
                            if (is_numeric($l)) $l = $r;
                            $cols[] = $l;
                        }
                    }
                    
                    foreach ($cols as &$col) {
                        $col = $fields[$col]['name'];
                    }
                    unset($col);
                    
                    if ($details[0] == 'one')
                        $idx[] = "KEY `idx_$k` (`".implode('`, `', $cols).'`)';
                }
            }
        }
        return $idx;
    }
    
    public function buildCreateTableSql()
    {
        $fields = $this->meta->getFields();
        
        if (!$fields)
            throw new Exception("Tried to create table for object {$this->meta->class} but it doesn't declare fields");
        
        $table = '`'.str_replace('`', '', $this->meta->table).'`';
        $connector = $this->manager->getConnector();
        $engine = $connector->engine;
        
        $primary = $this->meta->primary;
        $fields = static::buildFields();
        if (is_array($fields))
            $fields = implode(",\n  ", $fields);
        
        $query = "CREATE TABLE $table (\n  ";
        $query .= $fields;
        
        $indexes = $this->buildTableConstraints();
        if ($indexes) {
            $query .= ",\n  ".implode(",\n  ", $indexes);
        }
        
        $query .= "\n)";
        if ($engine == 'mysql') {
            $query .= ' ENGINE=InnoDB';
        }
        
        return $query;
    }
}
