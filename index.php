<?php

	$include_directory = 'includes';

	if (!isset($_SERVER['REWRITE_ENABLED']) || !$_SERVER['REWRITE_ENABLED']) {
		if (in_array($_SERVER['REQUEST_URI'], array('', '/', '/index.php'))) {
			$_SERVER['PATH_INFO']   = '/';
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
		}
	} elseif (isset($_SERVER['PATH_INFO'])) {
		$_SERVER['REQUEST_URI'] .= $_SERVER['PATH_INFO']; 
		$_SERVER['PATH_INFO']    = NULL;
	}

	// Step back until we find our includes directory (should be 1 at most)

	while (!is_dir($include_directory)) {
		$include_directory = '..' . DIRECTORY_SEPARATOR . $include_directory;
	}

	// Change to our includes directory

	define('APPLICATION_ROOT', realpath(dirname($include_directory)));

	chdir($include_directory);

	// Boostrap!

	require 'init.php';
