<?php

	/**
	 * You can change the configuration if you've created an independent
	 * configuration by setting its name here.
	 *
	 * Example: define('CONFIGURATION', 'development');
	 *
	 * The above example would load configuration information from the
	 * the development.conf file or, if not found, the config/development directory.
	 */

	define('CONFIGURATION', 'default');

	include 'functions.php';
	include 'core.php';

	$config = iw::init(CONFIGURATION);

	/**********************************************************************************************
	 * CUSTOM INITIALIZATION LOGIC CAN FOLLOW
	 **********************************************************************************************/