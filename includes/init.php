<?php

	require_once 'core.php';

	iw::init();

	$config = iw::getConfig();

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

	// Initialize the Database

	if (
		isset($config['database']['disabled']) &&
		!$config['database']['disabled']
	)  {

		if (
			!isset($config['database']['type']) ||
			!isset($config['database']['name'])
		) {
			throw new fProgrammerException (
				'Database support requires specifying the type and name.'
			);
		}

		$database_user = (isset($config['database']['user']))
			? $config['database']['user']
			: NULL;

		$database_password = (isset($config['database']['password']))
			? $config['database']['password']
			: NULL;

		$database_host = (isset($config['database']['host']))
			? $config['database']['host']
			: NULL;

		if (is_array($database_host) && count($database_host)) {
			$database_host = $database_host[array_rand($database_host)];
		}

		fORMDatabase::attach(new fDatabase(
			$config['database']['type'],
			$config['database']['name'],
			$database_user,
			$database_password,
			$database_host
		));

		foreach (fORMSchema::retrieve()->getTables() as $full_table) {
			if (stripos($full_table, '.') !== FALSE) {
				list($schema, $table) = explode('.', $full_table);
				if ($schema == 'inkwell') {
					fORM::mapClassToTable(fORM::classize($table), $full_table);
				}
			}
		}
	}

	// Initialize the Session

	fSession::setPath(
		iw::getWriteDirectory(implode(DIRECTORY_SEPARATOR, array(
			'tmp',
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

	// Initialize Date and Time Information

	if (isset($config['constants']['default_timezone'])) {
		fTimestamp::setDefaultTimezone(
			$config['constants']['default_timezone']
		);
	}
	if (
		isset($config['date_formats'])    &&
	    is_array($config['date_formats'])
	) {
		foreach ($config['date_formats'] as $name => $format) {
			fTimestamp::defineFormat($name, $format);
		}
	}

