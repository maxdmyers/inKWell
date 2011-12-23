<?php

	return iw::createConfig('Controller', array(

		// The pages root is relative to the overall controller, by default
		// '/user/controllers' and prepended by the baseURL.  So the index page
		// in your default section if this is set to 'pages' is:
		//
		// '/user/controllers/default/pages/index.php'

		'pages_root' => 'pages',

		// We add a catch all route which will allow our PagesController to
		// handle any routes which were no better suited for other controllers
		// as well as our error pages

		'routes' => array(
			'/*' => 'PagesController::load'
		),
	));
