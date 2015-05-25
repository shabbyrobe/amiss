<?php
namespace Amiss\Test\Helper;

class ModelDataTestCase extends \Amiss\Test\Helper\DataTestCase
{
    /**
     * @var Amiss\Sql\Manager
     */
    public $manager;
    
    public function getMapper()
    {
        $mapper = \Amiss\Sql\Factory::createMapper(array(
            'dbTimeZone'=>'UTC',
        ));
        $mapper->objectNamespace = 'Amiss\Demo';
        return $mapper;
    }
    
    public function getManager()
    {
        return \Amiss\Sql\Factory::createManager($this->db, $this->mapper);
    }
    
    public function setUp()
    {
        parent::setUp();
        
        \Amiss\Sql\ActiveRecord::_reset();
        
        $this->db = $this->getConnector();
        $this->db->exec($this->readSqlFile(AMISS_BASE_PATH.'/doc/demo/schema.{engine}.sql'));
        $this->db->exec($this->readSqlFile(AMISS_BASE_PATH.'/doc/demo/testdata.sql'));

        $this->db->queries = 0;
        
        $this->mapper = $this->getMapper();
        $this->manager = $this->getManager();
        \Amiss\Sql\ActiveRecord::setManager($this->manager);
    }
}

