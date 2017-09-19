<?php
/**
 * An URI/URL Handler
 *
 * @author	Maiz
 * @since	2014-07-18
 */
class Uri
{

	/**
	 * Detect SSL protocol
	 *
	 * @version	2014-07-18
	 * @return	boolean Is SSL or not
	 */
	static function is_ssl()
	{
		if ( isset($_SERVER['HTTPS']) )
		{
			if ( 'on' == strtolower($_SERVER['HTTPS']) )
			{
				return true;
			}
			if ( '1' == $_SERVER['HTTPS'] )
			{
				return true;
			}
		}
		elseif ( isset($_SERVER['SERVER_PORT']) and ( '443' == $_SERVER['SERVER_PORT'] ) )
		{
			return true;
		}
		return false;
	}


	/**
	 * Generate an URL with HTTP queries
	 *
	 * @version	2014-07-18
	 * @param	string	URL's domain
	 * @param	string	The reset of URL
	 * @param	array	Extra queries
	 * @return	string	URL
	 */
	static function get($host, $uri = '', $args = array())
	{
		$http = self::is_ssl()==false ? 'http://' : 'https://';
		$url = $http.rtrim($host, '/').'/'.ltrim($uri, '/');
		if ($args)
		{
			$args = http_build_query($args);
			if (strstr($uri, '?'))
			{
				$url .= '&' . $args;
			}
			else
			{
				$url .= '?' . $args;
			}
		}
		return $url;
	}
	
	
	/**
	 * Modify the HTTP queries based on the current URL
	 *
	 * @version	2014-07-18
	 *
	 * @param	array	The queries, rules:
	 * 'arg1' => string // arg1=string
	 * 'arg1' => null // delete arg1
	 *
	 * @param	array	Extra options，default value:
	 * 'reset_all' => false	// delete all existing queries
	 * 'keep' => array()	// retained some queries based on the above deletion
	 * 'url' => ''			// replace current URL with a new one, including all the URI except HTTP queries, 
	 *						// like http://s2.abc.com/search
	 *
	 * @return	string	New URL
	 */
	static function current($args = array(), $opts = array())
	{
		// combine current URL
		$s = $_SERVER['SERVER_PORT']=='443' ? 's' : '';
		$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . $s;
		$port = in_array($_SERVER['SERVER_PORT'], array('80','443')) ? '' : ':'.$_SERVER['SERVER_PORT'];
		$url = $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . '/';
		$url .= ltrim($_SERVER['REQUEST_URI'], '/');
		
		// get current queries
		$query = array();
		$url_arr = parse_url($url);
		parse_str($url_arr['query'], $query);
		
		// delete all URL queries
		if (isset($opts['reset_all']) and $opts['reset_all']==true)
		{
			foreach ($query as $key => $value)
			{
				if (in_array($key, $opts['keep'])) { continue; }
				unset($query[$key]);
			}
		}
		
		// update queries
		if ($args)
		{
			foreach ($args as $key => $value)
			{
				if (is_null($value))
				{
					unset($query[$key]);
				}
				else
				{
					$query[$key] = $value;
				}
			}
		}
		
		// replace URL
		if (isset($opts['url']) and $opts['url'])
		{
			$new_url_arr = parse_url($opts['url']);
			unset($new_url_arr['query']);
			$url_arr = $new_url_arr;
		}
		
		// rebuild URL
		$url_arr['query'] = http_build_query($query);
		$url = (isset($url_arr['scheme'])?$url_arr['scheme'].'://':'').$url_arr['host'].(isset($url_arr['port'])?':'.$url_arr['port']:'').$url_arr['path'].($url_arr['query']?'?'.$url_arr['query']:'').(isset($url_arr['fragment'])?'#'.$url_arr['fragment']:'');
		
		return $url;
	}
	
	
} // END class Uri

/* END */