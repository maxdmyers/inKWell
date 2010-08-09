<?php

	return array(

		'table'            => 'auth.users',
		'password_columns' => array('login_password'),
		'image_columns'    => array('avatar'),
		'fixed_columns'    => array('date_created', 'date_last_accessed'),

		'order'            => array(
			'id'           => 'asc'
		),

		// Defines the maximum number of login attempts to afford in
		// a certain time frame.  This can be, default is 5 every 15
		// minutes.

		'max_login_attempts' => '5/15 minutes'

	);
