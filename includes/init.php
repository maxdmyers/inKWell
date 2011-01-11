<?php

	require_once 'core.php';

	$config = iw::init();

	// Initialize Error Reporting

	if (isset($config['inkwell']['error_level'])) {
		error_reporting($config['inkwell']['error_level']);
	}

	if (
		isset($config['inkwell']['display_errors']) &&
		$config['inkwell']['display_errors']
	) {
		fCore::enableErrorHandling('html');
		fCore::enableExceptionHandling('html');
	} elseif (isset($config['inkwell']['error_email_to'])) {
		fCore::enableErrorHandling($config['inkwell']['error_email_to']);
		fCore::enableExceptionHandling($config['inkwell']['error_email_to']);
	}

	// Initialize the Session

	fSession::setPath(
		iw::getWriteDirectory(implode(DIRECTORY_SEPARATOR, array(
			'.tmp',
			'sessions'
		)))
	);

	$session_length = (isset($config['inkwell']['session_length']))
		? $config['inkwell']['session_length']
		: '30 minutes';

	if (
		isset($config['inkwell']['persistent_session']) &&
		$config['inkwell']['persistent_sessions']
	) {
		fSession::enablePersistence();
		fSession::setLength($session_length, $session_length);
	} else {
		fSession::setLength($session_length);
	}
	fSession::open();

	// Initialize the Databases

	if (
		isset($config['database']['disabled'])  &&
		!$config['database']['disabled']        &&
		isset($config['database']['databases'])
	)  {

		if (!is_array($config['database']['databases'])) {
			throw new fProgrammerException (
				'Databases must be configured as an array.'
			);
		}

		foreach ($config['database']['databases'] as $name => $settings) {

			if (!is_array($settings)) {
				throw new fProgrammerException (
					'Database settings must be configured as an array.'
				);
			}

			if (!isset($settings['type']) || !isset($settings['name'])) {
				throw new fProgrammerException (
					'Database support requires specifying the type and name.'
				);
			}

			$database_user = (isset($settings['user']))
				? $settings['user']
				: NULL;

			$database_password = (isset($settings['password']))
				? $settings['password']
				: NULL;

			$database_host = (isset($settings['host']))
				? $settings['host']
				: NULL;

			$database_role = (isset($settings['role']))
				? $settings['role']
				: 'both';

			if (is_array($database_host) && count($database_host)) {

				$target = iw::makeTarget('iw', 'database_host['. $name . ']');

				if (!($stored_host = fSession::get($target, NULL))) {

					$host_index    = array_rand($database_host);
					$database_host = $database_host[$host_index];

					fSession::set($target, $database_host);

				} else {

					$database_host = $stored_host;
				}
			}

			fORMDatabase::attach(new fDatabase(
				$settings['type'],
				$settings['name'],
				$database_user,
				$database_password,
				$database_host
			), $name, $database_role);
		}
	}

	// Initialize Date and Time Information

	if (isset($config['constants']['default_timezone'])) {
		fTimestamp::setDefaultTimezone(
			$config['constants']['default_timezone']
		);
	}

	if (
		isset($config['inkwell']['date_formats'])    &&
	    is_array($config['inkwell']['date_formats'])
	) {
		foreach ($config['inkwell']['date_formats'] as $name => $format) {
			fTimestamp::defineFormat($name, $format);
		}
	}

	// Load the Scaffolder if we have a configuration for it

	if (isset($config['scaffolder'])) {
		iw::loadClass('Scaffolder');
	}

	// Run the router if we're not in command line mode

	if (strtolower(php_sapi_name()) != 'cli') {
		require_once 'routing.php';
	}
