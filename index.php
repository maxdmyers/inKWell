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
	if (!($data = Moor::run())) {
		try {
			$data = self::render(View::retrieve(View::MASTER));
		} catch (fProgrammerException $e) {}
	}

	if (is_object($data)) {
		switch(strtolower(get_class($data))) {
			case 'view':
				$data->render();
				break;
			case 'ffile':
			case 'fimage':
				$data->output();
				break;
			default:
				echo serialize($data);
				break;
		}
	} else {
		echo $data;
	}
