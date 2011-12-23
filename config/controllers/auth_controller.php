<?php

	return iw::createConfig('Controller', array(

		// Request formats in this array will support Basic HTTP authentication
		// on resources which call the login method.  If login is accessed
		// directly via URL, HTTP authentication is not called, but instead,
		// the login view is presented.

		'http_auth_formats' => array('html', 'json', 'xml'),

		// The login view which is shown when AuthController::login is accessed
		// as page.  The default is 'pages/login.php'

		'login_view' => 'pages/login.php',

		// If a user accesses a login page directly as opposed to a page which
		// requires authorization they will be redirected after login to the
		// following URL.

		'login_success_url' => '/dashboard/',

		// The AuthController uses a host controller class for its actual
		// rendering to the world.  The host controller class is simply used
		// to render the actual not_authorized view.  The default is the
		// 'PagesController' (official inkwell extension)

		'host_controller' => 'PagesController',
		
		// The method which will be called on the host controller in the event
		// a user is not authorized.  The default is 'notAuthorized'

		'host_method' => 'notAuthorized'

	));
