<?php

	return iw::createConfig('Library', array(

		// The directory relative to $_SERVER['DOCUMENT_ROOT'] in which
		// user defined controllers are stored.

		'controller_root'             => 'user/controllers',

		// The default request format for standard browser based requests.

		'default_request_format'      => 'html',

		// The default request format for AJAX/XHR browser based requests.

		'default_ajax_request_format' => 'json',

		// The sections array allows you to define any number of base URLs
		// with separate page titles.  Each base URL will be detected
		// and used as the final component of the basepath.  The default
		// section is alway 'default' and it's assignment here simply allows
		// you to customize the title easily.

		'sections'        => array(

			'default'     => array(
				'title'   => 'inKWell Site',
				'use_ssl' => FALSE
			),
		),

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

			'not_authorized'     => array(
				'handler'        => NULL,
				'header'         => 'HTTP/1.1 401 Not Authorized',
				'message'        => 'The requested resource requires authorization'
			),

			'forbidden'          => array(
				'handler'        => NULL,
				'header'         => 'HTTP/1.1 403 Forbidden',
				'message'        => 'You do not have permission to view the requested resource'
			),

			'not_found'          => array(
				'handler'        => NULL,
				'header'         => 'HTTP/1.1 404 Not Found',
				'message'        => 'The requested resource could not be found'
			),

			'not_allowed'        => array(
				'handler'        => NULL,
				'header'         => 'HTTP/1.1 405 Method Not Allowed',
				'message'        => 'The requested resource does not support this method'
			),

			'not_acceptable'     => array(
				'handler'        => NULL,
				'header'         => 'HTTP/1.1 406 Not Acceptable',
				'message'        => 'The requested resource is not available in your accepted parameters'
			)
		)
	));
