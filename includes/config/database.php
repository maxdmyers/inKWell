<?php

	return iw::createConfig('Global', array(

		'disabled' => FALSE,

		'type'     => 'postgresql',
		'name'     => 'inkwelldemo_dotink_org',
		'user'     => 'inkwelldemo',
		'password' => 'inkwell123',

		// If the host parameter is configured as an array then inKWell will
		// select a random host to pull data from.  This can be good for
		// "round-robin" hunting, however, database replication has to be
		// nearly instant.

		'host'     => array('127.0.0.1')

	));
