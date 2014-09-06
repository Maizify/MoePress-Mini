<?php
if (!defined('MOEPRESS_VERSION')) { die('Access Forbidden'); }

/**
 * Demo Controller
 *
 */
class Controller extends MoePress
{
	protected $argc = array(
		'__index' => 0,			// default method does not accept any argument
		'name' => 2,			// 'name' method accept 2 arguments only
		'tag' => array(1, 2)	// 'tag' method accept 1 to 2 arguments
	);


	/**
	 * The default method
	 * URL example: http://your-site.com/
	 */
	public function __index()
	{
		// load model
		$this->model('home_model.php');
		$model = new Home_Model();
		$model->run();
		
		// load library
		$this->library('lib_helloworld.php');
		$lib = new Lib_HelloWord();
		$lib->run();
		
		// load view,
		// the string 'MoePress' will be passed as 'home_name' variable for view
		$this->data['home_name'] = 'MoePress';
		$this->view('home.php');
	}
	
	
	/**
	 * The 'name' method
	 * URL example: http://your-site.com/name/mark/zuckerberg
	 */
	public function name($firstname, $lastname)
	{
		echo 'Your name is: ['.htmlentities($firstname).' '.htmlentities($lastname).']';
	}
	
	
	/**
	 * The 'tag' method, URL example:
	 * http://your-site.com/tag/book
	 * http://your-site.com/tag/book/2
	 * http://your-site.com/tag/album/3
	 */
	public function tag($tag, $page = 1)
	{
		$tags = array('book', 'album');
		if (!in_array($tag, $tags)) {
			echo 'Tag ['.htmlentities($tag).'] does not exist.';
			return;
		}
		echo 'Tag: ['.htmlentities($tag).'], current page: ['.htmlentities($page).']';
	}
	

} // END class

/* END file */