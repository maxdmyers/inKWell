<?php

	$include_directory = 'includes';

	// 

	if (!isset($_SERVER['PATH_TRANSLATED'])) {
		if ($_SERVER['REQUEST_URI'] == '/' || empty($_SERVER['REQUEST_URI'])) {
			$_SERVER['PATH_INFO']   = '/';
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
		}
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
