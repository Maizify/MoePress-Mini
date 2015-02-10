<?php

class Database
{
	static protected $mysqls = array();
	
	
	static public function get_mysql($name = 'default')
	{
		$name = $name ? $name : 'default';
		if (isset(Database::$mysqls[$name]))
		{
			return Database::$mysqls[$name];
		}
		switch ($name)
		{
			case 'default':
				if (defined('IS_LOCALHOST') and IS_LOCALHOST)
				{
					$config = MoePress::$config['mysql']['localhost'];
				}
				else
				{
					$config = MoePress::$config['mysql']['remotehost'];
				}
				break;
			default:
				return null;
		}
		$db = new MySQL($config['database'], $config['username'], $config['password'], $config['host']);
		Database::$mysqls[$name] = $db;
		return $db;
	}
	
} // END class Database

/* END */