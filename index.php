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
		!is_dir($include_directory);
		//
		// Modifier
		//
		$include_directory = realpath('..' . DIRECTORY_SEPARATOR . $include_directory)
	);
	//
	// Define our application root as the directory containing the includes folder
	//
	define('APPLICATION_ROOT', dirname($include_directory));
	define('MAINTENANCE_FILE', APPLICATION_ROOT . DIRECTORY_SEPARATOR . 'MAINTENANCE');
	//
	// Boostrap!
	//
	require $include_directory . DIRECTORY_SEPARATOR . 'init.php';
	//
	// Check for and include maintenance file if it exists
	//
	if (file_exists(MAINTENANCE_FILE)) {
		include MAINTENANCE_FILE;
		exit(-1);
	}
	//
	// Include our routing logic and run the router.
	//
	require $include_directory . DIRECTORY_SEPARATOR . 'routing.php';
	//
	// Run the router and render its return value
	//
	try {
		$data = NULL;
		$data = ($data !== NULL) ? $data : Moor::run();
		$data = ($data !== NULL) ? $data : View::retrieve();
	} catch (Exception $e) {
		//
		// Panic here, attempt to determine what state we're in, see if some
		// errors handlers are callable or if we're totally fucked.  In the
		// end, throw the exception and let Flourish handle it appropriately.
		//
		throw $e;
	}
	//
	// Handle outputting of non-object data
	//
	if (!is_object($data)) {
		echo $data;
		exit(1);
	}
	//
	// Output different objects differently
	//
	switch(strtolower(get_class($data))) {
		case 'view':
			$data->render();
			exit(1);
		case 'ffile':
		case 'fimage':
			$data->output(FALSE);
			exit(1);
		default:
			echo serialize($data);
			exit(1);
	}
