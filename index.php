<?php
define('MOEPRESS_VERSION', '1.1');

/**
 * MoePress Mini
 *
 * A multi-level controller PHP framework
 *
 * @version 1.1
 * @update 2014-09-06
 * @since 2013-10-06
 * @author Cosify.com
 * @documentation http://www.cosify.com/work/moepress-mini
 *
 */
class MoePress
{
	static public $MP = NULL;			// MoePress Instance
	static public $config = array();	// global config (via config.php)
	
	protected $base_path = '';
	protected $model_path = '';
	protected $view_path  = '';
	protected $library_path = '';
	
	private $ob_level = null;			// to be used when load views
	private $loaded_files = array(		// to ensure files not being loaded twice
		'model' => array(),
		'library' => array()
	);
	
	public $site_url = '';				// current site's URL
	public $current_url = '';			// current URL
	public $data = array();				// variables used in views
	
	protected $argc = array();			// the limitation of the number of controller's arguments
	
	
	/**
	 * Init Controller
	 * DO NOT call this method more than ONCE.
	 *
	 * @version 2013-10-09
	 */
	public function __init()
	{
		// init file path
		$this->base_path = rtrim(getcwd(), '/') . '/';
		$this->model_path = $this->base_path . 'model/';
		$this->view_path = $this->base_path . 'view/';
		$this->library_path = $this->base_path . 'library/';
		
		// URLs
		$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'n') ? 's' : '';
		$sp = strtolower($_SERVER['SERVER_PROTOCOL']);
		$protocol = substr($sp, 0, strpos($sp, '/')) . $s;
		$port = $_SERVER['SERVER_PORT']=='80' ? '' : ':'.$_SERVER['SERVER_PORT'];
		$url = $protocol . '://' . $_SERVER['SERVER_NAME'] . $port . '/';
		
		$this->site_url = $url;
		if (MoePress::$config['base_uri']) {
			$this->site_url .= trim(MoePress::$config['base_uri'], '/') . '/';
		}
		$this->current_url = $url . ltrim($_SERVER['REQUEST_URI'], '/');
	}
	

	/**
	 * Load view
	 *
	 * @version 2013-10-06
	 * @usage
	 *   // simply load a view
	 *   $this->view('home_view.php');
	 *
	 *   // pass specified variable(s) to the view
	 *   $this->view('home_view.php', array('home_name'=>'My Home'));
	 *
	 *   // or you can set variable(s) in $this->data, the following two lines is equal to the above one
	 *   $this->data['home_name'] = 'My Home';
	 *   $this->view('home_view.php');
	 *
	 *   // get the view as a string(rather then output to browser right away) so that you can edit it later
	 *   $view_html_string = $this->view('home_view.php', array(), true);
	 *
	 * @param string $file the file path
	 * @param array $data the variables used in view. Array key is the variable's name.
	 * @param boolean $return whether output the view to browser directly or return it as a string variable
	 * @return object|string if $return==false, then return $this, otherwise return view string
	 */
	public function view($file = '', $data = array(), $return = false)
	{
		if ($data) {
			$data = (is_object($data)) ? get_object_vars($data) : $data;
			$this->data = array_merge($this->data, $data);
		}
		extract($this->data);
		
		if ($this->ob_level === null) {
			$this->ob_level = ob_get_level();
		}
		ob_start();
		
		if (file_exists($this->view_path.$file)) {
			include($this->view_path.$file);
		} else {
			throw new Exception('View file "'.$file.'" does not exist.');
		}
		
		if ($return == true) {
			$buffer = ob_get_contents();
			@ob_end_clean();
			return $buffer;
		}
		
		if (ob_get_level() > $this->ob_level + 1) {
			ob_end_flush();
		}
		
		return $this;
	}


	/**
	 * Load model
	 *
	 * @version 2013-10-06
	 * @usage
	 *   // load single model
	 *   $this->model('user_model.php');
	 *
	 *   // load multiple models
	 *   $this->model(array('user_model.php', 'home_model.php'));
	 *
	 * @param string|array $files the file path(s) of model, multiple files are supported
	 * @return object $this
	 */
	public function model($files = array())
	{
		$this->load('model', $files);
		return $this;
	}
	
	
	/**
	 * Load library
	 *
	 * @version 2013-10-06
	 * @usage see method model()
	 *
	 * @param string|array $files the file path(s) of library, multiple files are supported
	 * @return object $this
	 */
	public function library($files = array())
	{
		$this->load('library', $files);
		return $this;
	}
	
	
	
	/**
	 * Load file (model / library)
	 *
	 * @version 2013-10-06
	 *
	 * @param string $type choose "model" or "library"
	 * @param string $files the file path(s), multiple files are supported
	 */
	private function load($type = '', $files = array())
	{
		switch ($type) {
		case 'model':
			$path = $this->model_path;
			break;
		case 'library':
			$path = $this->library_path;
			break;
		default:
			throw new Exception('Unknow type "'.$type.'" while loading files.');
			break;
		}
		
		if (is_string($files)) {
			$files = array($files);
		}
		
		foreach ($files as $file) {
			if ($this->loaded_files[$type][$file]==true) {
				// this file has been loaded, so we do nothing here
			} else if (file_exists($path.$file)) {
				require_once($path.$file);
				$this->loaded_files[$type][$file] = true;
			} else {
				throw new Exception(ucwords($type).' file "'.$file.'" does not exist.');
			}
		}
	}
	
	
	
	/**
	 * Helper function: Get site URL
	 *
	 * @version 2013-10-06
	 *
	 * @return string
	 */
	public function site_url($uri = '')
	{
		return $this->site_url . ($uri ? trim($uri, '/') : '');
	}
	
	
	
	/**
	 * Helper function: Get current URL
	 *
	 * @version 2013-10-09
	 *
	 * @return string
	 */
	public function current_url()
	{
		return $this->current_url;
	}
	
	
	
	/**
	 * Helper function: Redirect to other URL
	 *
	 * @version 2013-10-06
	 *
	 * @param string $url
	 * @param int $code http response code
	 */
	public function redirect($url = '', $code = 302)
	{
		if (!preg_match('/^http/', $url)) {
			$url = $this->site_url($url);
		}
		header("Location: ".$url, true, $code);
		exit;
	}
	
	
	
	/**
	 * Run MoePress
	 *
	 * @version 2014-09-06
	 */
	static public function run()
	{
		$base_path = rtrim(getcwd(), '/') . '/';
		$config_file = $base_path.'config.php';
		$controller_path = $base_path.'controller/';
		
		// if the config file exists, then load it.
		// note that this config file 'config.php' is not requisite
		$_CONFIG = array();
		if (file_exists($config_file)) {
			require_once($config_file);
		}
		MoePress::$config = $_CONFIG;
		
		// if $_CONFIG exists in config.php, then do something according to the configurations
		if ($_CONFIG) {
			
			// auto load files before running controller
			if (is_array($_CONFIG['auto_load']) and count($_CONFIG['auto_load'])>0) {
				foreach ($_CONFIG['auto_load'] as $file) {
					if (!file_exists($base_path.$file)) {
						throw new Exception('The auto-loaded file "'.$file.'" does not exists.');
					}
					require_once($base_path.$file);
				}
			}
			
		}
		
		// get the requrest URI for routing
		$request_uri = preg_replace('/\?(.*)/', '', $_SERVER['REQUEST_URI']);
		// if a base URI is setted, then remove the base URI from the requrest URI
		if ($_CONFIG['base_uri']) {
			$request_uri = str_replace(trim($_CONFIG['base_uri'], '/'), '', $request_uri);
		}
		$request_uri = trim($request_uri, '/');
		
		// routing
		// note that the $request_uri can be matched and changed more than onece
		if (is_array($_CONFIG['routes']) and count($_CONFIG['routes']) > 0) {
			foreach ($_CONFIG['routes'] as $pattern => $subject) {
				if (preg_match($pattern, $request_uri)) {
					$request_uri = preg_replace($pattern, $subject, $request_uri);
				}
			}
		}

		// find controller
		$uris = explode('/', $request_uri);
		$controller_args = array();
		$controller_file = $controller_path.'main.php';
		while (count($uris) > 0) {
			$file = $controller_path.implode('/', $uris).'/main.php';
			if (file_exists($file)) {
				$controller_file = $file;
				break;
			} else {
				// if the URI is not a controller, then use it as controller method & arguments
				array_unshift($controller_args, array_pop($uris));
			}
		}
		
		// load controller
		if (!file_exists($controller_file)) {
			throw new Exception('Controller "'.$controller_file.'" does not exist.');
		}
		require_once($controller_file);
		
		// instantiate controller object
		if (!class_exists('Controller')) {
			throw new Exception('Controller class does not exist in file "'.$controller_file.'".');
		}
		$MP = new Controller();
		$MP->__init();
		MoePress::$MP = $MP;
		
		// find controller method
		$method = '__index';
		// if the name of first args value exists in controller, use it as default method
		if ($controller_args and method_exists($MP, $controller_args[0])) {
			$method = array_shift($controller_args);
		}
		
		if (!method_exists($MP, $method)) {
			throw new Exception('Controller method "'.$method.'" does not exist.');
		}
		
		// check the number of controller arguments
		if ($MP->argc[$method]) {
			$limit = $MP->argc[$method];
			// the limitation should be [min_num, max_num]
			if (is_numeric($limit)) {
				$limit = array($limit, $limit);
			}
			if (!is_array($limit)) {
				throw new Exception('The limitation of controller arguments is incorrect.');
			}
			$count = count($controller_args);
			if ( !($count >= $limit[0] and $count <= $limit[1]) ) {
				throw new Exception('The number of controller\'s arguments is incorrect.');
			}
		}
		
		// pass the args to the controller method, and run it
		call_user_func_array(array($MP, $method), $controller_args);
		
		// finished.
	}

} // END class MoePress



/**
 * Dump formatted variable for debugging
 *
 * @version 2013-03-16
 *
 * @param mixed $value
 */
function v($value)
{
	echo '<pre>';
	if (func_num_args()==0) {
		echo 'NULL';
	} else {
		if ($value) {
			print_r($value);
		} else {
			if (is_bool($value)) {
				echo 'false';
			} else if (is_numeric($value)) {
				echo '0';
			} else if (is_array($value)) {
				echo "Array\n(\n)";
			} else if (is_string($value)) {
				echo '""';
			} else if (is_null($value)) {
				echo 'null';
			} else {
				print_r($value);
			}
		}
	}
	echo '</pre>';
}



// here we go
try {

	MoePress::run();
	
} catch (Exception $e) {

	if (MoePress::$config['debug'] != false) {
		$traces = $e->getTrace();
		echo '<h1>Exception</h1>';
		echo '<div>'.$e->getMessage().'</div>';
		foreach ($traces as $trace) {
			echo '<ul>';
			echo '<li>File: '.$trace['file'].'</li>';
			echo '<li>Line: '.$trace['line'].'</li>';
			echo '<li>In function "'.$trace['function'].'" of class "'.$trace['class'].'"</li>';
			echo '</ul>';
		}
	} else {
		echo '<h1>Exception</h1>';
		echo '<div>Try to contact the webmaster.</div>';
	}
	
}

/* END file of Moepress-Mini */