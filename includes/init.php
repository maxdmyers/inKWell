<?php

	/**
	 * You can change the configuration if you've created an independent
	 * configuration by setting its name here.
	 *
	 * Example: define('CONFIGURATION', 'dev_config');
	 *
	 * The above example would load configuration information from the
	 * the dev_config.php file or, if not found, the dev_config directory.
	 */

	define('CONFIGURATION', NULL);

	include 'functions.php';
	include 'core.php';

	/**
	 * You can add customized initialization logic here
	 */

	$config = iw::init(CONFIGURATION);

	/**
	 * If we are running via command line interface, return here as we do not
	 * need any routing.
	 */

	if (strtolower(php_sapi_name()) == 'cli') {
		return;
	}

	/**
	 * Include our routing logic and run the router.
	 */

	include 'routing.php';
	Moor::setRequestParamPattern('[A-Za-z0-9_-]+');
	Moor::run();
