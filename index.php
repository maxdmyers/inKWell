<?php
	//
	// If our document root has been moved to a subdirectory of the actual application
	// directory, then we need to find it.
	//
	for (
		//
		// Initial assignment
		//
		$include_directory = 'includes';
		//
		// While Condition
		//
		!is_dir($include_directory));
		//
		// Modifier
		//
		$include_directory = '..' . DIRECTORY_SEPARATOR . $include_directory
	);
	//
	// Define our application root as the directory containing the includes folder
	//
	define('APPLICATION_ROOT', dirname(realpath($include_directory)));
	//
	// Boostrap!
	//
	require $include_directory . DIRECTORY_SEPARATOR . 'init.php';
	//
	// Include our routing logic and run the router.
	//
	//
	include 'routing.php';
	$view = Moor::run();
