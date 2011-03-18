<?php

	/**
	 * Below is a list of routes which Moor (the router) will try to resolve
	 * when the /includes/routing.php file is called.  In addition to the
	 * below routes, any controller configurations containing a 'routes' key
	 * will have its routes run as well in the order of general route
	 * specificity.
	 */

	return iw::createConfig('Core', array(
		'/' => function(){

			User::authorize('inkwell', 'dotink');

			echo '<h1>Session ID</h1>';
			fCore::expose(session_id());

			echo '<h1>User Sessions</h1>';
			fCore::expose(UserSessions::build());

			echo '<h1>User Roles</h1>';
			fCore::expose(
				User::retrieveLoggedIn()->buildAuthRoles()->call('getName')
			);

			echo '<h1>User ACL</h1>';
			fCore::expose(
				fauthorization::getUserACLs()
			);

			echo '<h1>Check Permissions</h1>';
			fCore::expose(
				User::checkACL(2, 'users')
			);

		}
	));
