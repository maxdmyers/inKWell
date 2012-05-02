<?php

	return iw::createConfig('Controller', array(
		//
		// Where to store pages.  This is as subdirectory of your writable folder.
		//
		'storage_path' => 'kwiki/pages',
		//
		// The title of the wiki
		//
		'title' => 'Kwiki',
		//
		// Your disqus shortname.  If this is set, comments will be enabled.
		//
		'disqus_id' => NULL,
		//
		// Your Google Analytics / Urchin Analytics ID
		//
		'ga_ua_id'  => NULL,
		//
		// The default route is for any pages in /wiki/ -- changing this effectively changes
		// your base directory.
		//
		'routes' => array(
			'/wiki/*' => 'KwikiController::show'
		)
	));
