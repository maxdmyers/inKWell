<?php

	return array(

		// The standard controller class allows for errors to be custom
		// configured based on keys.  Controller::triggerError('not_found')
		// for example would use the information provided by the 'not_found'
		// key below.
		//
		// Custom error configurations are expected to be arrays containing
		// the following key => value pairs:
		//
		//      'handler'  : A custom callback to handle the error
		//      'header'   : A header to be sent when triggered
		//      'messsage' : A default error message
		//
		// Errors which are not configured will fallback to using
		// Controller::triggerHardError() with default 500 internal server
		// error headers and a generic message

		'errors'                 => array(
			'not_found'          => array(
				'handler'        => 'PagesController::notFound',
				'header'         => 'HTTP/1.0 404 Not Found',
				'message'        => 'The requested resource could not be found'
			),
			'not_authorized'     => array(
				'handler'        => 'AuthController::login',
				'header'         => 'HTTP/1.0 401 Not Authorized',
				'message'        => 'The requested resource requires authorization'
			),
			'forbidden'          => array(
				'handler'        => 'PagesController::forbidden',
				'header'         => 'HTTP/1.0 403 Forbidden',
				'message'        => 'You do not have permission to view the requested resource'
			)
		)
	);