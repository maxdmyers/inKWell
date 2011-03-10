<?php

	require_once 'functions.php';
	require_once 'core.php';

	/**
	 * You can add customized initialization logic here, including overriding
	 * how or where the configuration is derived from.
	 */

	$config = iw::init();

	/**
	 * Below this point is the standard configuration logic for inKWell, this
	 * includes the attachment of databases, registering error handling, date
	 * formats, timezone, etc.
	 */

	// Initialize Error Reporting

	if (isset($config['inkwell']['error_level'])) {
		error_reporting($config['inkwell']['error_level']);
	}

	if (isset($config['inkwell']['display_errors'])) {
		if ($config['inkwell']['display_errors']) {
			fCore::enableErrorHandling('html');
			fCore::enableExceptionHandling('html');
		} elseif (isset($config['inkwell']['error_email_to'])) {
			$admin_email = $config['inkwell']['error_email_to'];
			fCore::enableErrorHandling($admin_email);
			fCore::enableExceptionHandling($admin_email);
			ini_set('display_errors', 0);
		} else {
			ini_set('display_errors', 0);
		}
	}

	// Include any interfaces

	if (isset($config['inkwell']['interfaces'])) {

		foreach ($config['inkwell']['interfaces'] as $interface_directory) {
			$available_interfaces = glob(implode(DIRECTORY_SEPARATOR, array(
				APPLICATION_ROOT,
				$interface_directory,
				'*.php'
			)));

			foreach ($available_interfaces as $available_interface) {

				$interface = pathinfo($available_interface, PATHINFO_FILENAME);

				if (!interface_exists($interface, FALSE)) {
					include $available_interface;
				}
			}
		}
	}

	// Initialize Date and Time Information, this has to be before any
	// time related functions.

	if (isset($config['inkwell']['default_timezone'])) {
		fTimestamp::setDefaultTimezone(
			$config['inkwell']['default_timezone']
		);
	} else {
		throw new fProgrammerException(
			'Please configure your timezone'
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

			$database_target = explode('::', $name);

			$database_name   = !empty($database_target[0])
				? $database_target[0]
				: NULL;

			$database_role   = isset($database_target[1])
				? $database_target[1]
				: 'both';

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
			), $database_name, $database_role);
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
