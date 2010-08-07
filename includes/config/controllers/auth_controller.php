<?php

	return array(

		// Request formats in this array will support Basic HTTP authentication
		// on resources which call the login method.  If login is accessed
		// directly via URL, HTTP authentication is not called.

		'http_auth_formats' => array('html', 'json', 'xml'),

		'login_success_url' => '/admin/'
	);
