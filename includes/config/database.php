<?php

	return iw::createConfig('Core', array(

		'disabled'         => TRUE,

		'databases'        => array(

			// Multiple databases can be configured.  If database support is
			// enabled above the default database is always 'default', but it
			// is possible to add independent databases and then configure
			// ActiveRecords/models to use those databases using the 'database'
			// configuration element in their independent configurations.

			'default'      => array(

				// The database types used/allowed by inKWell reflect whatever
				// is currently supported by Flourish, examples at the time of
				// creating this file include: db2, mssql, mysql, oracle,
				// postgresql, and sqlite

				'type'     => '',

				'name'     => '',

				// Authentication information if required

				'user'     => '',
				'password' => '',

				// If the host parameter is configured as an array then inKWell
				// will select a random host to pull data from.  This can be
				// good for "round-robin" hunting.  The particular database
				// server which a visitor connects to for the first time will
				// be stored in their session to ensure any effect they have on
				// the data will be reflected instantly to them.  Replication
				// between databases must be handled elsewhere, and is presumed
				// to be for the most part on-the-fly.

				'host'     => array('127.0.0.1'),

				// You can specify a role for the database such as
				// 'read', 'write', or 'both' -- if NULL or missing,
				// the default is both

				'role'     => 'both'

			),
		),
	));
