<?php
if (!defined('MOEPRESS_VERSION')) { die('Access Forbidden'); }

/**
 * Demo Controller
 *
 */
class Controller extends MoePress
{

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
		
		// load view
		$this->data['home_name'] = 'MoePress';
		$this->view('home.php');
	}
	

} // END class

/* END file */