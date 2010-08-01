<?php

	/**
	 * Access Control List controller for updating permissions
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class ACLController extends AuthedController
	{

		/**
		 * Creates a new controller and handles all initialization.  Customize
		 * this method for authorization checks, setting of controller info,
		 * etc.
		 *
		 * @param string $action
		 * @return void
		 */
		protected function prepare($resource_key)
		{

			$this->data = array(
				'action'                => Moor::getActiveShortMethod(),
				'resource_key'          => $resource_key,
				'auth_actions'          => AuthActions::build()
			);

		}

		protected function render($view = NULL)
		{
			if (self::checkRequestFormat('html')) {
				$view_file = implode(DIRECTORY_SEPARATOR, array(
					'access_controls',     // default access control views
					$this->action . '.php' // file
				));
			}

			switch (iw::makeTarget(self::getRequestFormat(), $view)) {

				case 'html::update':
					parent::render();

					$this->view
						 -> add  ('primary_section',       self::requestView($view_file))
						 -> pack ('resource_key',          $this->resource_key)
						 -> pack ('users',                 $this->users)
						 -> pack ('auth_roles',            $this->auth_roles)
						 -> pack ('auth_actions',          $this->auth_actions)
						 -> pack ('page_id',               $this->action)
						 -> push ('page_classes',          $this->resource_key)
						 -> push ('page_classes',          'access_controls')
						 -> push ('page_title',            'Update ACLs');
					break;

				default:
					self::triggerNotFound();
					break;
			}
		}

		/**
		 * Initializes the ACLController
		 */
		static public function __init()
		{
		}

		/**
		 * Update
		 */

		static public function update()
		{

			if (!$resource_key = fRequest::get('resource_key', 'string?')) {
				self::triggerNotFound(self::MSG_TYPE_ERROR);
			}

			$controller = Controller::build(__CLASS__, $resource_key);

			if (fRequest::isPost()) {

				$controller->auth_roles = fRequest::get('auth_roles', 'array', array());

				foreach ($controller->auth_roles as $pkey => $permission) {
					foreach ($permission as $resource_key => $bit_values) {
						$auth_role            = new AuthRole($pkey);
						$auth_role_permission = $auth_role->fetchPermission($resource_key);
						$new_permissions      = 0;
						foreach ($bit_values as $bit_value) {
							$new_permissions |= $bit_value;
						}
						$auth_role_permission->setBitValue($new_permissions);
						$auth_role_permission->store();
					}
				}

				$controller->users = fRequest::get('users', 'array', array());

				foreach ($controller->users as $pkey => $permission) {
					foreach ($permission as $resource_key => $bit_values) {
						$user            = new User($pkey);
						$user_permission = $user->fetchPermission($resource_key);
						$new_permissions = 0;
						foreach ($bit_values as $bit_value) {
							$new_permissions |= $bit_value;
						}
						$user_permission->setBitValue($new_permissions);
						$user_permission->store();
					}
				}

			}

			$controller->users      = Users::build();
			$controller->auth_roles = AuthRoles::build();

			$controller->render('update');
		}

	}
