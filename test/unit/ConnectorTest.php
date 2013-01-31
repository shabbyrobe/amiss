<?php
namespace Amiss\Test\Unit;

use Amiss\Sql\Connector;

/**
 * This test doesn't make any real connections, so it should not be necessary to
 * extend from DataTestCase
 */
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
     * @covers Amiss\Sql\Connector::disconnect
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
     * @covers Amiss\Sql\Connector::setAttribute
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
     * @covers Amiss\Sql\Connector::setAttribute
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
     * @covers Amiss\Sql\Connector::getAttribute
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
     * @covers Amiss\Sql\Connector::getAttribute
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
     * @covers Amiss\Sql\Connector::errorInfo
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
     * @covers Amiss\Sql\Connector::errorInfo
     */
    public function testErrorInfoDisconnected()
    {
        $c = new Connector('sqlite::memory:');
        $this->assertNull($c->errorInfo());
    }

    /**
     * @group unit
     * @covers Amiss\Sql\Connector::errorCode
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
     * @covers Amiss\Sql\Connector::errorCode
     */
    public function testErrorCodeDisconnected()
    {
        $c = new Connector('sqlite::memory:');
        $this->assertNull($c->errorCode());
    }
    
    /**
     * @group unit
     * @covers Amiss\Sql\Connector::exec
     * @covers Amiss\Sql\Connector::lastInsertId
     * @covers Amiss\Sql\Connector::prepare
     * @covers Amiss\Sql\Connector::query
     * @covers Amiss\Sql\Connector::quote
     * @covers Amiss\Sql\Connector::beginTransaction
     * @covers Amiss\Sql\Connector::commit
     * @covers Amiss\Sql\Connector::rollback
     * @dataProvider dataForProxies
     */
    public function testProxies($method, $args=array())
    {
        $connector = $this->getMockBuilder('Amiss\Sql\Connector')
            ->setMethods(array('createPDO'))
            ->disableOriginalConstructor()
            ->getMock()
        ;
        $pdo = $this->getMockBuilder('stdClass')
            ->setMethods(array($method))
            ->getMock()
        ;
        $connector->expects($this->any())->method('createPDO')->will($this->returnValue($pdo));
        $expect = $pdo->expects($this->once())->method($method);
        $connector->connect();
        
        if ($args) {
            $equals = array();
            foreach ($args as $a) {
                $equals[] = $this->equalTo($a);
            }
            call_user_func_array(array($expect, 'with'), $equals);
        }
        
        call_user_func_array(array($connector, $method), $args);
    }
    
    public function dataForProxies()
    {
        return array(
            array('exec', array('yep')),
            array('lastInsertId'),
            array('prepare', array('stmt', array('k'=>'v'))),
            array('quote', array('q')),
            array('beginTransaction'),
            array('commit'),
            array('rollback'),
            
            // query just takes whatever you throw at it
            array('query'),
            array('query', array('foo')),
            array('query', array('stmt', 'foo', 'bar', 'baz', 'qux')),
        );
    }

    /**
     * @group unit
     * @covers Amiss\Sql\Connector::create
     * @dataProvider dataForCreateHost
     */
    public function testCreateHost($hostKey)
    {
        $conn = \Amiss\Sql\Connector::create(array($hostKey=>'dbhost'));
        $this->assertEquals('mysql:host=dbhost;', $conn->dsn);
    }
    
    public function dataForCreateHost()
    {
        return array(
            array('host'),
            array('HOst'),
            array('hostName'),
            array('hOSTAGe'),
            array('hOSTAGe'),
            array('host_name'),
            array('server'),
        );
    }

    /**
     * @group unit
     * @covers Amiss\Sql\Connector::create
     * @dataProvider dataForCreateUser
     */
    public function testCreateUser($key)
    {
        $conn = \Amiss\Sql\Connector::create(array($key=>'myuser'));
        $this->assertEquals('myuser', $conn->username);
    }
    
    public function dataForCreateUser()
    {
        return array(
            array('u'),
            array('UNAME'),
            array('unagi'),
            array('user'),
        );
    }

    /**
     * @group unit
     * @covers Amiss\Sql\Connector::create
     * @dataProvider dataForCreatePassword
     */
    public function testCreatePassword($key)
    {
        $conn = \Amiss\Sql\Connector::create(array($key=>'passw0rd'));
        $this->assertEquals('passw0rd', $conn->password);
    }
    
    public function dataForCreatePassword()
    {
        return array(
            array('p'),
            array('Pass'),
            array('paSSword'),
            array('passwd'),
            array('plage noire'),
        );
    }

    /**
     * @group unit
     * @covers Amiss\Sql\Connector::create
     * @dataProvider dataForCreateOptions
     */
    public function testCreateOptions($key)
    {
        $conn = \Amiss\Sql\Connector::create(array($key=>array('a'=>'b')));
        $this->assertEquals(array('a'=>'b'), $conn->driverOptions);
    }
    
    public function dataForCreateOptions()
    {
        return array(
            array('driverOptions'),
            array('driveroptions'),
            array('options'),
        );
    }

    /**
     * @group unit
     * @covers Amiss\Sql\Connector::create
     * @dataProvider dataForCreateConnectionStatements
     */
    public function testCreateConnectionStatements($key)
    {
        $conn = \Amiss\Sql\Connector::create(array($key=>array('a', 'b')));
        $this->assertEquals(array('a', 'b'), $conn->connectionStatements);
    }
    
    public function dataForCreateConnectionStatements()
    {
        return array(
            array('connectionstatements'),
            array('CONNECTIONstatements'),
            array('statements'),
        );
    }

    /**
     * @group unit
     * @covers Amiss\Sql\Connector::create
     */
    public function testCreateDsn()
    {
        $value = 'mysql:host=localhost;dbname=foobar';
        $conn = \Amiss\Sql\Connector::create(array('dsn'=>$value));
        $this->assertEquals($value, $conn->dsn);
    }

    /**
     * @group unit
     * @covers Amiss\Sql\Connector::create
     */
    public function testCreateDsnOverridesHost()
    {
        $value = 'mysql:host=localhost;dbname=foobar';
        $conn = \Amiss\Sql\Connector::create(array('dsn'=>$value, 'host'=>'whoopee'));
        $this->assertEquals($value, $conn->dsn);
    }
}
