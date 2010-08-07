<?php

	return array(

		// We use flourish for tons of stuff, including init, so it comes first

		'f*'             => 'includes/lib/flourish/classes',

		// Moor is our router, so that comes second

		'Moor*'          => 'includes/lib/moor',

		// Then we have our Controllers, Models, RecordSets

		'*Controller'    => 'user/controllers',
		'active_records' => 'user/models',
		'record_sets'    => 'user/models/sets',

		// And our Core Library

		'library'        => 'includes/lib'
	);
