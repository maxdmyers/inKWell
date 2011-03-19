<?php

	return iw::createConfig('Controller', array(
	
		// Which directory, relative to the writable folder, to use for caching
		// images.

		'cache_directory' => 'cache/images',
	
		// Routes which allow for accessing the image via URL
	
		'routes' => array(
			'/images/:entry/:pkey/:column/width/:width/:name.:format'   => 'DynamicImagesController::scale',
			'/images/:entry/:pkey/:column/height/:height/:name.:format' => 'DynamicImagesController::scale',
			'/images/:entry/:pkey/:column/scale/:percent/:name.:format' => 'DynamicImagesController::scalePercent'
		)
	));
