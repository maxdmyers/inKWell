<?php

	$config = array(

		'autoloaders'                => array(

			// We use flourish for tons of stuff, including init, so it comes first

			'f*'                     => 'includes/lib/flourish/classes',

			// Moor is our router, so that comes second

			'Moor*'                  => 'includes/lib/moor',

			// Then we have our Controllers, Models, RecordSets

			'*Controller'            => 'user/controllers',
			'active_records'         => 'user/models',
			'record_sets'            => 'user/models/sets',

			// And our Core Library

			'library'                => 'includes/lib'
		),

		'global'                     => array(

			// General Settings

			'write_directory'        => 'writable',

			// Error Reporting Information

			'display_errors'         => TRUE,
			'error_level'            => E_ALL,
			'error_email_to'         => 'webmaster@dotink.org',

			// Session information

			'persistent_sessions'    => FALSE,
			'session_length'         => '1 day',

			// Time and Date Information

			'default_timezone'       => 'America/Los_Angeles',
			'date_formats'           => array(
				'console_date'       => 'M jS, Y',
				'console_time'       => 'g:ia',
				'console_timestamp'  => 'M jS, Y @ g:ia'
			)
		),

		'database'                   => array(

			'disabled'               => FALSE,

			'type'                   => 'postgresql',
			'name'                   => 'inkwelldemo_dotink_org',
			'user'                   => 'inkwelldemo',
			'password'               => 'inkwell123',
			'host'                   => '127.0.0.1'

		),

		'scaffolder'                 => array(

			'disabled'               => FALSE,
			'scaffolding_root'       => 'scaffolding',

			'autoloaders'            => array(
				'**Controller'       => 'ARController::__make',
				'_active_records'    => 'ActiveRecord::__make',
				'_record_sets'       => 'RecordSet::__make'
			)
		),

		'controller'                 => array(

			'not_found_handler'      => 'PagesController::notFound',
			'not_authorized_handler' => 'AuthorizationController::login',
			'forbidden_handler'      => 'PagesController::forbidden'

		),

		'view'                       => array(

			'view_root'              => 'user/views'
		),

		'pages_controller'           => array(

			'pages_root'             => 'user/controllers/pages',

			'sections'               => array(
				'default'            => 'inKWell Site',
				'admin'              => 'inKWell Console',
				'documentation'      => 'inKWell Documentation'
			)
		),

		'authorization_controller'   => array(

			'http_auth_formats'      => array('html', 'json', 'xml'),
			'login_success_url'      => '/admin/'
		),

		'users'                      => array(

			'max_login_attempts'     => '5/15 minutes'
		)


	);

