<?php

	return iw::createConfig('Core', array(

		// The root directory for inkWell, generally this will be the
		// APPLICATION_ROOT -- this is an absolute path relative to the root
		// of the filesystem

		'root_directory' => APPLICATION_ROOT,

		// This is the writable directory where everything from sessions
		// to file uploads will be stored.  The default .htaccess file
		// forbids access to any file beginning with sess_ which is the PHP
		// default for storing sessions.

		'write_directory' => 'writable',

		// Here you can configure whether or not to display errors, or e-mail
		// them to you.  During development you will likely want to keep
		// display_errors set to TRUE, while once in production you may
		// wish to set the error_email_to to your e-mail address.

		'display_errors' => TRUE,
		'error_level'    => E_ALL,
		'error_email_to' => NULL,

		// Enabling persistent sessions will cause the user's session to stay
		// alive even after they close the browser.  This is not recommended
		// sitewide, but is an available option, if you would like to enable
		// a persistent session depending on some kind of logic, see the
		// documentation for Controller::persistSession();

		'persistent_sessions' => FALSE,
		'session_length'      => '1 day',

		// Default timezones follow the standard PHP notation, a list of
		// these can be located here: http://php.net/manual/en/timezones.php

		'default_timezone' => 'America/Los_Angeles',

		// Date formats can be added for quick reference when using dates
		// returned by the system.  Example being that if you had a column
		// in a database which was a date and wanted it to be represented
		// in a particular format you could do something like this:
		//
		//      $user->prepareLastAccessedTimestamp('access_timestamp')
		//

		'date_formats'          => array(

			'console_date'      => 'M jS, Y',
			'console_time'      => 'g:ia',
			'console_timestamp' => 'M jS, Y @ g:ia'
		),

		// Interfaces can be loaded in bulk from the array of interface
		// directories.  By default this is /includes/lib/interfaces

		'interfaces' => array(
			'includes/lib/interfaces'
		),
	));
