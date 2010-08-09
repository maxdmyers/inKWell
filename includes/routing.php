<?php

	require_once 'init.php';

	Moor::setRequestParamPattern('[A-Za-z0-9_-]+');

	Moor::

		route('/server_information', 'phpinfo') ->

	run();
