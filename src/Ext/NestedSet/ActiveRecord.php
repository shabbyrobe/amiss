<?php
namespace Amiss\Ext\NestedSet;

class ActiveRecord extends \Amiss\Sql\ActiveRecord
{
    public static function getNestedSetManager($class=null)
    {
        return static::getDependency('nestedSetManager', $class);
    }
    
    public static function setNestedSetManager(Manager $manager=null)
    {
        return static::setDependency('nestedSetManager', $manager);
    }
    
    public static function renumber()
    {
        return static::getDependency('nestedSetManager')->renumber(get_called_class());
    }
    
    public function insert()
    {
        $this->beforeInsert();
        $this->beforeSave();
        return static::getDependency('nestedSetManager')->insert($this);
    }
    
    public function delete()
    {
        $this->beforeDelete();
        return static::getDependency('nestedSetManager')->delete($this);
    }
}
