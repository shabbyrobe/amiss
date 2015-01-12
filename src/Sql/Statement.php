<?php
namespace Amiss\Sql;

class Statement extends \PDOStatement
{
    private $connector;

    protected function __construct($connector)
    {
        $this->connector = $connector;
    }

	function bindColumn($column, &$param, $type=\PDO::PARAM_STR, $maxlen=null, $driverOptions=null) 
	{
        return ($ret = parent::bindColumn($column, $param, $type, $maxlen, $driverOptions)) ? $this : $ret;
	}

	function bindParam($parameter, &$variable, $type=\PDO::PARAM_STR, $length=null, $driverOptions=null) 
	{
        return ($ret = parent::bindParam($variable, $param, $type, $length, $driverOptions)) ? $this : $ret;
	}

	function bindValue($parameter, $value, $type=\PDO::PARAM_STR) 
	{
        return ($ret = parent::bindValue($parameter, $value, $type)) ? $this : $ret;
	}

	function closeCursor() 
	{
        return ($ret = parent::closeCursor()) ? $this : $ret;
	}

	function execute($inputParameters=null) 
	{
        ++$this->connector->queries;
        return ($ret = parent::execute($inputParameters)) ? $this : $ret;
	}

    /**
     * It's called exec on PDO, but execute on PDOStatement. Go figure.
     */
	function exec($inputParameters=null) 
	{
        ++$this->connector->queries;
        return ($ret = parent::execute($inputParameters)) ? $this : $ret;
	}

	function nextRowset() 
	{
        return ($ret = parent::nextRowset()) ? $this : $ret;
	}

	function setAttribute($attribute, $value) 
	{
        return ($ret = parent::setAttribute($attribute, $value)) ? $this : $ret;
	}

	function setFetchMode($mode, $params=null) 
	{
        return ($ret = parent::setFetchMode($mode, $params)) ? $this : $ret;
	}
}
