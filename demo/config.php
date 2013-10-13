<?php
if (!defined('MOEPRESS_VERSION')) { die('Access Forbidden'); }

/**
 * Config for demo
 */

$_CONFIG = array(
	
	// if MoePress isn't in the root path of your domain ( e.g. http://mysite.com/projects/moepress-mini/demo/ ),
	// then the 'base_uri' should be filled with the extra path, that is, 'projects/moepress-mini/demo'.
	// otherwise, leave it empty.
	'base_uri' => '',

	// auto load some files before running controller
	'auto_load' => array(
		// some examples:
		//'model/home_model.php',
		//'library/lib_helloworld.php'
	),
	
	// regular expression is used
	'routes' => array(
		// the two parameters are swapped positions for controller 'home'
		//'#home/(.+)/(.+)#' => 'home/$2/$1'
	)
	
);