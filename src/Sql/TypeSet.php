<?php
namespace Amiss\Sql;

class TypeSet
{
	public $dbTimeZone;
	public $appTimeZone;
	
	private $dateTime;
	
	public function getDate()
	{
		return $this->getDateTime();
	}
	
	public function getTimestamp()
	{
		return $this->getDateTime();
	}
	
	public function getDateTime()
	{
		if (!isset($this->dateTime)) {
			$this->dateTime = new \Amiss\Sql\Type\Date('datetime', $this->dbTimeZone, $this->appTimeZone);
		}
		return $this->dateTime;
	}
	
	public function getAutoinc()
	{
		return new Type\Autoinc();
	}
}
