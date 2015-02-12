<?php
/**
 * A File Cache Handler (in JSON format)
 *
 * @author	Maiz
 * @since	2012-07-12
 */
class Cache
{
	static public $dir = 'cache/';
	static public $suffix = '.json';
	
	
	/**
	 * Delete cache
	 *
	 * @version	2012-07-12
	 *
	 * @param	string	The name of cache
	 * @return	boolean
	 */
	static public function delete($key)
	{
		return unlink(self::$dir . $key . self::$suffix);
	}
	
	
	/**
	 * Get cache
	 * Cache will be deleted if it is expired
	 *
	 * @version 2012-07-12
	 *
	 * @param	string	The name of cache
	 * @param	int		Expire time in seconds, 0==no expire time
	 * @return	mix		The cache value
	 */
	static public function get($key, $expire = 0)
	{
		$file = self::$dir . $key . self::$suffix;
		$value = file_get_contents($file);
		if (!$value)
		{
			return;
		}
		$time = filemtime($file);
		if ($expire > 0 and time() - $time >= $expire)
		{
			self::delete($key);
			return;
		}
		return json_decode($value);
	}
	
	
	/**
	 * Set cache
	 *
	 * @version 2012-07-12
	 *
	 * @param	string	The name of cache
	 * @param	mix		The value of cache
	 * @return	boolean	Whether it is cached successfully
	 */
	static public function set($key, $value)
	{
		if (!$key)
		{
			return false;
		}
		$path = explode('/', $key);
		array_pop($path);
		$path = self::$dir . implode('/', $path);
		self::mkdir($path);
		if (file_put_contents(self::$dir . $key . self::$suffix, json_encode($value)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	
	/**
	 * Create folder(s) for a given path
	 *
	 * @version 2015-02-12
	 *
	 * @param	string	The folder path
	 * @return	string	Original path with '/' at right end
	 */
	static public function mkdir($path = '')
	{
		$path = rtrim($path, '/');
		$dir = explode('/', $path);
		$temp = '';
		foreach ($dir as $value)
		{
			$temp .= $value.'/';
			if (!is_dir($temp)) { mkdir($temp, 0777); }
		}
		return $temp;
	}
	
	

} // END class Cache

/* END */