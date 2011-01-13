<?php

	// Change to our includes directory and run init

	if ($_SERVER['REQUEST_URI'] == '/' || empty($_SERVER['REQUEST_URI'])) {
		header('HTTP/1.1 302 Moved Temporarily');
		header('Location: /index.php' . $_SERVER['REQUEST_URI']);
	}

	chdir(implode(DIRECTORY_SEPARATOR, array(
		dirname(__FILE__),
		'includes'
	)));

	require 'init.php';
