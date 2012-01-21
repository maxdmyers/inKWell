<?php
	//
	// Handle '/' via a proxy URI.
	//
	if (!isset($_SERVER['REWRITE_ENABLED']) || !$_SERVER['REWRITE_ENABLED']) {
		if (in_array($_SERVER['REQUEST_URI'], array('', '/', '/index.php'))) {
			$_SERVER['PATH_INFO']   = '/';
			$_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'];
		}
	} elseif (isset($_SERVER['PATH_INFO'])) {
		$_SERVER['REQUEST_URI'] .= $_SERVER['PATH_INFO'];
		$_SERVER['PATH_INFO']    = NULL;
	}
	//
	// Set the Request Parm Pattern
	//
	$request_param_pattern = isset($config['inkwell']['request_param_pattern'])
		? $config['inkwell']['request_param_pattern']
		: '[A-Za-z0-9_-]+';

	Moor::setRequestParamPattern($request_param_pattern);
	//
	// Register all of our custom routes first
	//
	foreach ($config['routes'] as $route => $target) {
		Moor::route($route, $target);
	}
	//
	// Look for controller configurations which specify routes and sort them
	// by a specificity and then register them in the order of highest to
	// leaste specific.
	//
	$controller_configs = iw::getConfigsByType('Controller');
	$routes             = array();
	$ordered_routes     = array();

	foreach ($controller_configs as $controller_config) {

		if (isset($controller_config['routes'])) {

			if (!is_array($controller_config['routes'])) {
				throw new fProgrammerException(
					'Wrong data type for routes configuration.'
				);
			}

			foreach ($controller_config['routes'] as $route => $target) {

				$route_parts = explode('/', $route);
				$route_depth = count($route_parts);
				$specificity = $route_depth * 10;

				foreach ($route_parts as $route_part) {

					if (isset($route_part[0])) {

						switch ($route_part[0]) {
							case ':':
								$extra_points = 1;
								break;
							case '@':
								$extra_points = 2;
								break;
							default:
								$extra_points = strlen($route_part);
								break;
						}

						$specificity += $extra_points;
					}
				}

				$routes[$route] = array(
					'target'      => $target,
					'specificity' => $specificity
				);

			}
		}
	}

	foreach($routes as $route => $info) {
		$ordered_routes[$route] = $info['specificity'];
	}

	arsort($ordered_routes);

	foreach ($ordered_routes as $route => $specificity) {
		Moor::route($route, $routes[$route]['target']);
	}
