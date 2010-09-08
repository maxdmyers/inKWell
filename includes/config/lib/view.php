<?php

	return iw::createConfig('Library', array(

		'view_root'           => 'user/views',

		// A per Flourish's code minification, we can set minification modes of
		// either 'developer' or 'production'

		'minification_mode'   => 'development',

		// Please note that this will be relative to the inKWell write
		// directory.

		'cache_directory'     => 'cache'
	));
