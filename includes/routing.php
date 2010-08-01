<?php

	require_once 'init.php';

	Moor::setRequestParamPattern('[A-Za-z0-9_-]+');

	Moor::

		route('/server_information',                        'phpinfo'                          ) ->
		route('/login',                                     'AuthorizationController::login'   ) ->
		route('/logout',                                    'AuthorizationController::logout'  ) ->

		// Admin CP Routing

		route('/admin/acl/:resource_key/@action',           'ACLController::@action(lc)'       ) ->

		route('/admin/@entry/',                             '@entry(uc)Controller::manage'     ) ->
		route('/admin/:related_entry/:pkey/@entry/',        '@entry(uc)Controller::manage'     ) ->

		route('/admin/@entry/@action',                      '@entry(uc)Controller::@action(lc)') ->
		route('/admin/@entry/:pkey/@action',                '@entry(uc)Controller::@action(lc)') ->
		route('/admin/:related_entry/:pkey/@entry/@action', '@entry(uc)Controller::@action(lc)') ->

		// General Page Routing

		route('/*',                                         'PagesController::load'            ) ->

	run();
