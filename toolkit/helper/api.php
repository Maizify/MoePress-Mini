<?php
/**
 * A JSON API Formatted Outputter
 *
 * @author	Maiz
 * @since	2014-06-29
 * @example
 *	{
 *		'info': { // a common field
 *			error: 0, // error code, non-zero means error
 *			msg: '' // error message
 *		},
 *		'posts': [ // your custom data
 *			...
 *		]
 *	}
 */
class Api
{

	/**
	 * Output an error
	 *
	 * @version	2015-02-09
	 * @param	int		A non-zero number
	 * @param	string	Error message
	 * @param	array	Extra data
	 */
	static public function error($error_code = 0, $msg = '', $data = array())
	{
		$response = array(
			'info' => array(
				'error' => $error_code,
				'msg' => $msg
			)
		);
		self::output($response);
	}
	

	/**
	 * Output normal response
	 *
	 * @version	2015-02-09
	 * @param	array	Extra data
	 */
	static public function response($data = array())
	{
		$response = array(
			'info' => array(
				'error' => 0,
				'msg' => ''
			)
		);
		$response = array_merge($response, $data);
		self::output($response);
	}
	
	
	/**
	 * Output response, the response body can be customized
	 *
	 * @version	2014-06-29
	 */
	static protected function output($response)
	{
		header('Content-Type: application/json');
		echo json_encode($response);
	}

} // END class

/* END */