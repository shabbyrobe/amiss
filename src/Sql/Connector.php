<?php
namespace Amiss\Sql;

/**
 * Database connector.
 * 
 * When using a regular PDO, the connection is made immediately. This object is a 
 * stand-in for a PDO that defers connecting to the database until a connection 
 * is actually required.
 * 
 * The only change to PDO's default behaviour is that this class sets the error
 * mode to throw exceptions by default.
 * 
 * It also offers some enhancements - it will tell you when there is an active
 * transaction (unless you grab the internal PDO and start one directly)
 */
class Connector
{
    /**
     * Underlying database connection
     * Will be null if the Connector has not established a connection.
     * @var \PDO|null
     */
    public $pdo;
    
    /**
     * DSN for the database connection
     * @var string
     */
    public $dsn;
    
    /**
     * Database engine
     * @var string
     */
    public $engine;
    
    /**
     * Database username
     * @var string
     */
    public $username;
    
    /**
     * Database password
     * @var string
     */
    public $password;
    
    /**
     * Database driver options
     * @var array
     */
    public $driverOptions;
    
    /**
     * List of statements to run when the connection is established
     * This is mostly here to allow you to set the connection encoding.
     * @var array
     */
    public $connectionStatements;

    public $queries = 0;
    
    private $attributes=array();

    private $persistent = false;
    
    public function __clone()
    {
        $this->pdo = null;
    }
    
    public function __construct($dsn, $username=null, $password=null, array $driverOptions=null, array $connectionStatements=null)
    {
        $this->dsn = $dsn;
        $this->engine = strtolower(substr($dsn, 0, strpos($dsn, ':')));
        $this->username = $username;
        $this->password = $password;
        $this->driverOptions = $driverOptions ?: array();
        $this->connectionStatements = $connectionStatements ?: array();
    }
    
    public function __destruct()
    {
        $this->pdo = null;
    }

    /**
     * @ignore
     */
    public function __sleep()
    {
        $this->pdo = null;
        $keys = array_keys(get_object_vars($this));
        $keys[] = 'attributes';
        return $keys;
    }
    
    /**
     * Creates a Connector from an array of connection parameters.
     * @param array Parameters to use to create the connection
     * @return Amiss\Sql\Connector
     */
    public static function create(array $params)
    {
        $options = $host = $port = $database = $user = $password = $connectionStatements = null;
        
        foreach ($params as $k=>$v) {
            $k = strtolower($k);
            if (strpos($k, "host")===0 || $k == 'server' || $k == 'sys') {
                $host = $v;
            } elseif ($k=='port') {
                $port = $v;
            } elseif ($k=="database" || strpos($k, "db")===0) {
                $database = $v;
            } elseif ($k[0] == 'p') {
                $password = $v;
            } elseif ($k[0] == 'u') {
                $user = $v;
            } elseif ($k=='options' || $k=='driveroptions') {
                $options = $v;
            } elseif ($k=='connectionstatements' || $k=='statements') {
                $connectionStatements = $v;
            }
        }
       
        if (!isset($params['dsn'])) {
            $dsn = (isset($params['engine']) ? $params['engine'] : 'mysql').":host={$host};";
            if ($port) {
                $dsn .= "port=".$port.';';
            }
            if (!empty($database)) {
                $dsn .= "dbname={$database};";
            }
        }
        else {
            $dsn = $params['dsn'];
        }
        
        return new static($dsn, $user, $password, $options, $connectionStatements);
    }
    
    public function getPDO()
    {
        if ($this->pdo == null) {
            $this->pdo = $this->createPDO();
        }
        return $this->pdo;
    }
    
    public function createPDO()
    {
        if (isset($this->driverOptions[\PDO::ATTR_PERSISTENT])) {
            $this->persistent = $this->driverOptions[\PDO::ATTR_PERSISTENT];
        }
        if (!$this->persistent) {
            $this->attributes[\PDO::ATTR_STATEMENT_CLASS] = ['Amiss\Sql\Statement', [$this]];
        } else  {
            unset($this->attributes[\PDO::ATTR_STATEMENT_CLASS]);
        }

        $pdo = new \PDO($this->dsn, $this->username, $this->password, $this->driverOptions);
        
        if (!isset($this->attributes[\PDO::ATTR_ERRMODE])) {
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        if ($this->attributes) {
            foreach ($this->attributes as $k=>$v) {
                $pdo->setAttribute($k, $v);
            }
        }
        $this->attributes = null;
        
        foreach ($this->connectionStatements as $sql) {
            $pdo->exec($sql);
        }
        
        return $pdo;
    }
    
    public function isConnected()
    {
        return $this->pdo;
    }
    
    public function ensurePDO()
    {
        if ($this->pdo == null) {
            throw new \PDOException("Not connected");
        }
    }

    public function connect()
    {
        $this->getPDO();
    }
    
    /**
     * Allows the connector to be disconnected from the database
     * without nulling the connector object. This allows reconnection
     * later in the script.
     * 
     * This is an alternative to the standard PDO way of nulling all 
     * references to the PDO object, which also works with PDOConnector.
     * 
     * Regular PDO way (also works with Connector):
     *   $pdoConnector = null;
     * 
     * Using disconnect():
     *   $pdoConnector->query("SHOW PROCESSLIST");
     *   $pdoConnector->disconnect();
     *   $pdoConnector->query("SHOW PROCESSLIST");
     */
    public function disconnect()
    {
        $this->pdo = null;
    }
    
    public function setAttribute($attribute, $value)
    {
        if ($this->pdo == null) {
            $this->attributes[$attribute] = $value;
            return true;
        }
        else
            return $this->pdo->setAttribute($attribute, $value);
    }
    
    public function getAttribute($attribute)
    {
        if ($this->pdo == null) {
            return isset($this->attributes[$attribute]) ? $this->attributes[$attribute] : null;
        } else {
            return $this->pdo->getAttribute($attribute);
        }
    }
    
    /**
     * @see \PDO::beginTransaction
     */
    public function beginTransaction()
    {
        if (!$this->pdo) {
            $this->connect();
        }
        return $this->pdo->beginTransaction();
    }
    
    /**
     * @see \PDO::commit
     */
    public function commit()
    {
        if (!$this->pdo) {
            $this->connect();
        }
        return $this->pdo->commit();
    }
    
    public function rollBack()
    {
        if (!$this->pdo) {
            $this->connect();
        }
        return $this->pdo->rollBack();
    }
    
    public function errorCode()
    {
        if ($this->pdo == null) {
            return null;
        }
        return $this->pdo->errorCode();
    }
    
    public function errorInfo()
    {
        if ($this->pdo == null) {
            return null;
        }
        return $this->pdo->errorInfo();
    }
    
    public function exec($sql, $params=null)
    {
        if (!$this->pdo) {
            $this->connect();
        }
        ++$this->queries;
        if (!$params) {
            return $this->pdo->exec($sql);
        }
        else {
            $stmt = $this->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        }
    }

    /**
     * PDO calls it 'exec'. PDOStatement calls it 'execute'. Crazy!
     */
    public function execute($sql, $params=null)
    {
        return $this->exec($sql, $params);
    }

    public function execAll($statements, $transaction=false)
    {
        if (!$statements) {
            throw new \InvalidArgumentException();
        }
        if (!$this->pdo) {
            $this->connect();
        }

        $out = [];
        if ($transaction) {
            $this->beginTransaction();
        }
        foreach ($statements as $k=>$statement) {
            ++$this->queries;
            $out[$k] = $this->pdo->exec($statement);
        }
        if ($transaction) {
            $this->commit();
        }
        return $out;
    }
    
    public function lastInsertId()
    {
        $this->ensurePDO();
        return $this->pdo->lastInsertId();
    }
    
    public function prepare($sql, array $driverOptions=array())
    {
        if (!$this->pdo) {
            $this->connect();
        }
        $stmt = $this->pdo->prepare($sql, $driverOptions);
        if ($stmt instanceof \PDOStatement) {
            return $this->persistent ? new StatementWrapper($this, $stmt) : $stmt;
        } else {
            return $stmt;
        }
    }
    
    public function query()
    {
        if (!$this->pdo) {
            $this->connect();
        }
        $args = func_get_args();
    
        $stmt = call_user_func_array(array($this->pdo, 'query'), $args);
        if ($stmt instanceof \PDOStatement) {
            ++$this->queries;
            return $this->persistent ? new StatementWrapper($this, $stmt) : $stmt;
        } else {
            return $stmt;
        }
    }
    
    public function quote($string, $parameterType=null)
    {
        if (!$this->pdo) {
            $this->connect();
        }
        return $this->pdo->quote($string, $parameterType);
    }
}
