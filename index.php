<?php

	// Change to our includes directory and run init

	chdir(implode(DIRECTORY_SEPARATOR, array(
		dirname(__FILE__),
		'includes'
	)));

	require 'init.php';
