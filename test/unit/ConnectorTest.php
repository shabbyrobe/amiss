<?php

namespace Amiss\Test\Unit;

use Amiss\Connector;

class ConnectorTest extends \CustomTestCase
{
	/**
	 * @group unit
	 */
	public function testEngine()
	{
		$c = new Connector('pants:foo=bar');
		$this->assertEquals('pants', $c->engine);
	}
	
	/**
	 * @group unit
	 */
	public function testConnect()
	{
		$c = new Connector('sqlite::memory:');
		$this->assertNull($c->pdo);
		$c->exec("SELECT * FROM sqlite_master WHERE type='table'");
		$this->assertNotNull($c->pdo);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Connector::disconnect
	 */
	public function testDisconnect()
	{
		$c = new Connector('sqlite::memory:');
		$c->query("SELECT 1");
		$this->assertNotNull($c->pdo);
		$c->disconnect();
		$this->assertNull($c->pdo);
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Connector::setAttribute
	 */
	public function testDisconnectedSetAttribute()
	{
		$c = new Connector('sqlite::memory:');
		$this->assertNull($c->pdo);
		
		$c->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		$this->assertNull($c->pdo);
		$this->assertEquals(array(\PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC), $this->getProtected($c, 'attributes'));	
	}

	/**
	 * @group unit
	 * @covers Amiss\Connector::setAttribute
	 */
	public function testConnectedSetAttribute()
	{
		$pdo = $this->getMockBuilder('stdClass')
			->setMethods(array('setAttribute'))
			->getMock()
		;
		$pdo->expects($this->once())->method('setAttribute')->with(
			$this->equalTo(\PDO::ATTR_ERRMODE),
			$this->equalTo(\PDO::ERRMODE_EXCEPTION)
		);
		$c = new Connector('sqlite::memory:');
		$c->pdo = $pdo;
		$c->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);	
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Connector::getAttribute
	 */
	public function testDisconnectedGetAttribute()
	{
		$c = new Connector('sqlite::memory:');
		$this->setProtected($c, 'attributes', array(\PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC));
		$this->assertNull($c->pdo);
		
		$attr = $c->getAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE);
		$this->assertNull($c->pdo);
		$this->assertEquals($attr, \PDO::FETCH_ASSOC);	
	}

	/**
	 * @group unit
	 * @covers Amiss\Connector::getAttribute
	 */
	public function testConnectedGetAttribute()
	{
		$pdo = $this->getMockBuilder('stdClass')
			->setMethods(array('getAttribute'))
			->getMock()
		;
		$pdo->expects($this->once())->method('getAttribute')
			->with($this->equalTo(\PDO::ATTR_ERRMODE))
			->will($this->returnValue(\PDO::ERRMODE_EXCEPTION))
		;
		
		$c = new Connector('sqlite::memory:');
		$c->pdo = $pdo;
		$this->assertEquals(\PDO::ERRMODE_EXCEPTION, $c->getAttribute(\PDO::ATTR_ERRMODE));	
	}

	/**
	 * @group unit
	 * @covers Amiss\Connector::errorInfo
	 */
	public function testErrorInfoConnected()
	{
		$pdo = $this->getMockBuilder('stdClass')
			->setMethods(array('errorInfo'))
			->getMock()
		;
		$pdo->expects($this->once())->method('errorInfo');
		
		$c = new Connector('sqlite::memory:');
		$c->pdo = $pdo;
		$c->errorInfo();	
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Connector::errorInfo
	 */
	public function testErrorInfoDisconnected()
	{
		$c = new Connector('sqlite::memory:');
		$this->assertNull($c->errorInfo());	
	}

	/**
	 * @group unit
	 * @covers Amiss\Connector::errorCode
	 */
	public function testErrorCodeConnected()
	{
		$pdo = $this->getMockBuilder('stdClass')
			->setMethods(array('errorCode'))
			->getMock()
		;
		$pdo->expects($this->once())->method('errorCode');
		
		$c = new Connector('sqlite::memory:');
		$c->pdo = $pdo;
		$c->errorCode();	
	}
	
	/**
	 * @group unit
	 * @covers Amiss\Connector::errorCode
	 */
	public function testErrorCodeDisconnected()
	{
		$c = new Connector('sqlite::memory:');
		$this->assertNull($c->errorCode());	
	}
}
