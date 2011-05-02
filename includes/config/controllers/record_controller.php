<?php

	return iw::createConfig('Controller', array(

		'auto_scaffold' => TRUE,

		'sections'        => array(

			'admin'     => array(
				'title'   => 'inKWell Admin Panel',
				'use_ssl' => FALSE
			),
		),

		'routes' => array(
			'/admin/@entry/'                                           => '@entry(uc)Controller::manage',
			'/admin/@entry/@action'                                    => '@entry(uc)Controller::@action(lc)',
			'/admin/@entry/:slug/@action'                              => '@entry(uc)Controller::@action(lc)',

			'/admin/:related_entry/:related_slug/@entry/'              => '@entry(uc)Controller::manage',
			'/admin/:related_entry/:related_slug/@entry/@action'       => '@entry(uc)Controller::@action(lc)',
			'/admin/:related_entry/:related_slug/@entry/:slug/@action' => '@entry(uc)Controller::@action(lc)',
		),
	));
