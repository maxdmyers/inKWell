<?php

	return iw::createConfig('Library', array(

		// The directory relative to the inKWell root directory in which views
		// are stored.  Using the set() or add() method on a view will prepend
		// this directory.
		//
		// Example :
		// Code    : $controller->view->add('content', 'pages/home.html')
		// Loads   : <inkwell root>/<root_directory>/pages/home.html

		'root_directory' => 'user/views',

		// As per Flourish's code minification, we can set minification modes
		// of either 'developer' or 'production' -- The differences between
		// the two relate to caching and are outlined under the Minification
		// section at: http://flourishlib.com/docs/fTemplating
		//
		// If set to NULL, no minification will take place at all.

		'minification_mode' => 'development',

		// The cache directory is relative to the global write directory, 
		// default APPLICATION_ROOT/writable and is used to store
		// cached versions of minified Javascript and CSS.

		'cache_directory' => 'cache'
	));
