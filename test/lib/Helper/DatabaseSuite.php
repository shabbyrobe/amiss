<?php
namespace Amiss\Test\Helper;

class DatabaseSuite extends \PHPUnit_Framework_TestSuite
{
    public $connectionInfo;

    public function __construct($conn)
    {
        if (!is_array($conn)) {
            throw new \InvalidArgumentException();
        }
        $this->connectionInfo = $conn;
    }

    public function setUp()
    {
        \Amiss\Test\Helper\Env::instance()->connectionInfo = $this->connectionInfo;
        parent::setUp();
    }
}
