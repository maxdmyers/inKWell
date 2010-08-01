<?php

	require_once 'core.php';
	require_once 'config.php';

	iw::init($config);

	// Initialize Error Reporting

	if (isset($config['global']['error_level'])) {
		error_reporting($config['global']['error_level']);
	}

	if (
		isset($config['global']['display_errors']) && 
		$config['global']['display_errors']
	) {
		fCore::enableErrorHandling('html');
		fCore::enableExceptionHandling('html');
	} elseif (isset($config['global']['error_email_to'])) {
		fCore::enableErrorHandling($config['global']['error_email_to']);
		fCore::enableExceptionHandling($config['global']['error_email_to']);
	} else {
		throw new fProgrammerException (
			'Please configure display errors or set an error e-mail.'
		);
	}

	// Initialize the Database
	if (
		isset($config['global']['disable_database']) &&
		!$config['global']['disable_database']
	)  {

		if (
			!isset($config['global']['database_type']) ||
			!isset($config['global']['database'])
		) {
			throw new fProgrammerException (
				'Database support requires specifying the type and database.'
			);
		}

		$database_user = (isset($config['global']['database_user']))
			? $config['global']['database_user']
			: NULL;

		$database_password = (isset($config['global']['database_password']))
			? $config['global']['database_password']
			: NULL;

		$database_host = (isset($config['global']['database_host']))
			? $config['global']['database_host']
			: NULL;


		fORMDatabase::attach(new fDatabase(
			$config['global']['database_type'],
			$config['global']['database'],
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

	$session_length = (isset($config['global']['session_length']))
		? $config['global']['session_length']
		: '30 minutes';

	if (
		isset($config['global']['persistent_session']) &&
		$config['global']['persistent_sessions']
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
		isset($config['global']['date_formats'])    &&
	    is_array($config['global']['date_formats'])
	) {
		foreach ($config['global']['date_formats'] as $name => $format) {
			fTimestamp::defineFormat($name, $format);
		}
	}

