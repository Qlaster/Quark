<?php

	class db_adapter_dba
	{
		//Идентификатор открытой базы данных
		private $DBH;
	
	
		function __construct($file, $mode='c', $hadle='db4')
		{
			//Открываем соединение с базой
			$this->DBH = dba_open($file, $mode, $hadle);
		}
		
		function __destruct() 
		{
			//Закрываем соединение с базой
			dba_close($this->DBH);
		}
   
   
		function insert($key, $var)
		{
			return dba_insert($key, serialize($var), $this->DBH);
		}
		
		function update($key, $var)
		{
			return dba_replace($key, serialize($var), $this->DBH);
		}
		
		function select($key)
		{
			$res = dba_fetch($key, $this->DBH);
			if ($res) return unserialize($res);			
		}
		
		function delete($key)
		{
			return dba_delete($key, $this->DBH);
		}
		
		function exists($key)
		{
			return dba_exists($key, $this->DBH);
		}
		
		
		
		
		
		function firstkey()
		{
			return dba_firstkey($this->DBH);
		}
		
		function nextkey()
		{
			return dba_nextkey($this->DBH);
		}
		
		function optimize()
		{
			return dba_optimize($this->DBH);
		}
		
	}
