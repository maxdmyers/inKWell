<?php

	return array(

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
