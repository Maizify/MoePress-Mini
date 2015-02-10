<?php
/**
 * An User-submitted Data Handler
 *
 * @author	Maiz
 * @since	2014-07-18
 */
class Input
{

	/**
	 * Apply HTML entity encode to string, array or object 
	 * to ensure that the value is HTML-safe
	 *
	 * @version	2014-07-18
	 * @param	mix	Can be string, array or object
	 * @return	mix	Filtered result
	 */
	static function filter($value)
	{
		if (is_string($value))
		{
			// conver special chars to HTML
			$value = htmlentities($value, ENT_QUOTES, 'utf-8');
		}
		else if (is_array($value))
		{
			foreach ($value as $k => $v)
			{
				$value[$k] = self::filter($v);
			}
		}
		else if (is_object($value))
		{
			foreach ($value as $k => $v)
			{
				$value->{$k} = self::filter($v);
			}
		}
		return $value;
	}
	
	
	/**
	 * Apply HTML entity encode to a large text field
	 *
	 * @version	2014-07-18
	 * @param	string	Input string
	 * @return	string	Filtered result
	 */
	static function content_filter($value)
	{
		// conver special chars to HTML
		$value = htmlentities($value, ENT_QUOTES, 'utf-8');
		// conver newline chars to HTML
		$value = nl2br($value);
		// conver two spaces to HTML
		$value = str_replace('  ', '&nbsp;&nbsp;', $value);
		return $value;
	}
	
	
	/**
	 * Safen SQL
	 *
	 * @version	2014-07-18
	 * @param	string	SQL
	 * @return	string	Filtered result
	 */
	static function sql_filter($value)
	{
		$value = addslashes($value);
		return $value;
	}


	/**
	 * Get $_GET value, then apply decode and filter
	 *
	 * @version	2014-07-18
	 * @param	string		Value's key name
	 * @param	boolean		Whether to do filter
	 * @return	string|null	Return null when the key doesn't exist
	 */
	static function get($key, $filter = false)
	{
		if (!isset($_GET[$key])) { return null; }
		$value = $_GET[$key];
		$value = urldecode($value);
		if ($filter == true)
		{
			$value = input::filter($value);
		}
		return $value;
	}


	/**
	 * Get $_POST value, then apply decode and filter
	 *
	 * @version	2014-07-18
	 * @param	string		Value's key name
	 * @param	boolean		Whether to do filter
	 * @return	string|null	Return null when the key doesn't exist
	 */
	static function post($key, $filter = false)
	{
		if (!isset($_POST[$key])) { return null; }
		$value = $_POST[$key];
		if ($filter == true)
		{
			$value = input::filter($value);
			
		}
		return $value;
	}

} // END class input

/* END */