<?php

	// The inKWell autoloader works via a series of match parameters and
	// targets.
	//
	// Match parameters can be standard strings, strings with wildcards, or
	// class names.  If the string contains a * they are used as wildcards, so
	// for example the following would make any class beginning with
	// 'user' load from the 'includes/lib/user' directory:
	//
	//      'user*'  =>   'includes/lib/user'
	//
	// In the event a string does not contain any wildcards the autoloader
	// will try to determine whether or not it is a class.  If it is a class
	// it will run the magic method defined by iw::MATCH_CLASS_METHOD.  This
	// method is completely customizable, but is expected to to return TRUE or
	// FALSE based on it's custom match logic.  If the method returns TRUE
	// the autoloader will attempt to load the class from the target.
	//
	// Finally, if the match parameter contains no wilcards, is not a class or
	// does not have a match method set on the class, it will be treated as
	// a static match parameter, and try to load from the target directory
	// regardless.
	//
	// In ALL cases, if the class cannot be loaded from a target directory
	// the autoloader will move on to the next match => target pair.
	//
	// Since it is possible to recurse on autoloads, it is possible to make
	// any match a class which has not yet been loaded, however, would be
	// loaded if the array of autoloaders were to be called again.

	return iw::createConfig('Global', array(

		// We use flourish for tons of stuff, including init, so it comes first

		'f*'           => 'includes/lib/flourish/classes',

		// Moor is our router, so that comes second

		'Moor*'        => 'includes/lib/moor',

		// Our Core Library

		'library'      => 'includes/lib',

		// Then we have our Controllers, Models, RecordSets

		'Controller'   => 'user/controllers',
		'ActiveRecord' => 'user/models',
		'RecordSet'    => 'user/models/sets'

	));
