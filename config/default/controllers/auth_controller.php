<?php

	return iw::createConfig('Controller', array(
		//
		// The AuthController uses a host action for actually rendering to the world.
		//
		'host' => NULL,
		//
		// By default all request formats have Basic HTTP Authentication enabled.  Any request
		// formats added to this array will trigger form based login via the host action (see
		// above).  In most circumstances, 'html' will be the only request format added to this.
		//
		'non_http_auth_formats' => array(),
		//
		// If a user accesses a login page directly as opposed to a page which requires
		// authorization they will be redirected after login to the following URL.
		//
		'login_success_url' => '/',
		//
		// Our default routes
		//
		'routes' => array(
			'/login'  => 'AuthController::login',
			'/logout' => 'AuthController::logout',
		)
	));
