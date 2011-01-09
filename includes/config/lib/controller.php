<?php

	return iw::createConfig('Library', array(

		// The default request format for standard browser based requests.

		'default_request_format'      => 'html',

		// The default request format for AJAX/XHR browser based requests.

		'default_ajax_request_format' => 'json',

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
				'handler'        => NULL,
				'header'         => 'HTTP/1.0 404 Not Found',
				'message'        => 'The requested resource could not be found'
			),

			'not_authorized'     => array(
				'handler'        => NULL,
				'header'         => 'HTTP/1.0 401 Not Authorized',
				'message'        => 'The requested resource requires authorization'
			),

			'forbidden'          => array(
				'handler'        => NULL,
				'header'         => 'HTTP/1.0 403 Forbidden',
				'message'        => 'You do not have permission to view the requested resource'
			)
		)
	));
