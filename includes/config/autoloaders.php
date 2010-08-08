<?php

	// The inKWell autoloader works via a series of match parameters and
	// targets.
	//
	// Match parameters can be either a string or static method
	// callback represented as a string.  If the string contains a * they
	// are used as wildcard.  In the event it is a callback the class which
	// is attempting to be autoloaded will be passed and the callback is
	// expected to return TRUE or FALSE based on it's custom match logic.
	// If it returns true the target will be attempted.
	//
	// Targets, likewise are strings which can either be directories or
	// callbacks.  If the file is not found in a directory or the directory
	// does not appear to exist, the class will not be loaded via that target.
	// If the target is a callback, the the class which is attempting to be
	// autoloaded as will be passed to it.  The callback should determine
	// whether or not it can load that class, and if it can/does return TRUE,
	// FALSE otherwise.
	//
	// Since it is possible to recurse on autoloads, it is possible to make
	// any match or target callback on a class which has not yet been loaded,
	// however would be loaded if the array of autoloaders were to be called
	// again.

	return array(

		// We use flourish for tons of stuff, including init, so it comes first

		'f*'             => 'includes/lib/flourish/classes',

		// Moor is our router, so that comes second

		'Moor*'          => 'includes/lib/moor',

		// Our Core Library

		'library'        => 'includes/lib',

		// Then we have our Controllers, Models, RecordSets

		'Controller'     => 'user/controllers',
		'ActiveRecord'   => 'user/models',
		'RecordSet'      => 'user/models/sets'

	);
