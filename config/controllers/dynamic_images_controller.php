<?php

	return iw::createConfig('Controller', array(

		// The init method registers a hook callback for clearing classes, so
		// we preload this.

		'preload' => TRUE,

		// Which directory, relative to the writable folder, to use for caching
		// images.

		'cache_directory' => 'cache/images',

		// An array of valid output formats, default is jpg and png only

		'valid_formats' => array(
			'jpg', 'png'
		),

		// The image quality for cached images, default is 95

		'image_quality' => 95,

		// Routes which allow for accessing the image via URL

		'routes' => array(
			'/images/:entry/:slug/:column/width/:width/:name.:request_format'   => 'DynamicImagesController::scale',
			'/images/:entry/:slug/:column/height/:height/:name.:request_format' => 'DynamicImagesController::scale',
			'/images/:entry/:slug/:column/scale/:percent/:name.:request_format' => 'DynamicImagesController::scalePercent'
		)
	));
