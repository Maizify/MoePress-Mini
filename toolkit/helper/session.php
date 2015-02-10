<?php
/**
 * A Session Handler
 * Notice: session_start() should be called manually before using these methods
 *
 * @author	Maiz
 * @since	2013-12-05
 */
class Session
{
	
	/**
	 * Set a session
	 *
	 * @version	2013-12-05
	 * @param	string	Session's key
	 * @param	string	Session's value
	 */
	static function set($key, $value)
	{
		$_SESSION[$key] = $value;
	}
	
	
	/**
	 * Get a session
	 *
	 * @version	2013-12-05
	 * @param	string	Session's key
	 * @param	boolean	Whether to delete this session after getting it
	 */
	static function get($key, $unset = false)
	{
		$value = $_SESSION[$key];
		if ($unset == true)
		{
			self::delete($key);
		}
		return $value;
	}
	
	
	/**
	 * Output a session and then delete it
	 *
	 * @version	2013-12-05
	 * @param	string	Session's key
	 * @param	boolean	Whether to delete this session
	 */
	static function out($key, $unset = false)
	{
		echo $_SESSION[$key];
		if ($unset == true)
		{
			self::delete($key);
		}
	}
	
	
	/**
	 * Delete a session
	 *
	 * @version	2013-12-05
	 * @param	string	Session's key
	 */
	static function delete($key)
	{
		unset($_SESSION[$key]);
	}

} // END class Session

/* END */