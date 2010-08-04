<?php

	/**
	 * User model which provides direct access to a single user record using
	 * an instantiated instance, as well as user authorization methods.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class User extends ActiveRecord
	{

		const INVALID_LOGIN_MSG                = 'Username and/or password are invalid.';
		const MAX_LOGIN_ATTEMPTS_MSG           = 'You have attempted to login too many times, please try again %s.';

		const MAX_LOGIN_ATTEMPTS               = 5;
		const MAX_LOGIN_ATTEMPTS_TIME_COUNT    = 30;
		const MAX_LOGIN_ATTEMPTS_TIME_MEASURE  = 'minutes';

		const LOGIN_FIELD                      = 'username';

		static private   $maxLoginAttempts     = NULL;
		static private   $maxLoginAttemptsTime = NULL;
		static protected $logged_in_user       = NULL;

		/**
		 * Encrypts a password and sets it to the internal login_password
		 *
		 * @param string $password The password to set on the user
		 * @return User The User record for method chaining
		 */
		public function setLoginPassword($password)
		{
			$this->set('login_password', fCryptography::hashPassword($password));
			return $this;
		}

		/**
		 * Prepares and returns the auth hash for HTML views by
		 * obfuscating it.
		 *
		 * @param void
		 * @return string The obfuscated auth hash
		 */
		public function prepareLoginPassword()
		{
			return '<em>Encrypted Password</em>';
		}

		/**
		 * How to display the user record if it is used as a string
		 *
		 * @param void
		 * @return string The user's username
		 */
		public function __toString()
		{
			return $this->getUsername();
		}

		/**
		 * Fetches a user's permission for the given resource key.  If the
		 * permission does not exist in the database then the record is created
		 * with no permissions.
		 *
		 * @param string $resource_key The resource key to fetch permissions for
		 * @param boolean $inherit Whether or not to inherit permissions if there is no specific permission
		 * @return UserPermission The UserPermission record representing the resource key
		 */
		public function fetchPermission($resource_key, $inherit = FALSE)
		{
			try {
				$user_permission = new UserPermission(array(
					'user_id'      => $this->getId(),
					'resource_key' => $resource_key
				));
			} catch (fNotFoundException $e) {
				$user_permission = new UserPermission();
				$user_permission->setUserId($this->getId());
				$user_permission->setResourceKey($resource_key);

				if ($inherit) {

					$best_precision   = -1;
					$user_permissions = UserPermissions::build(array(
						'user_id=' => $this->getId()
					));

					foreach ($user_permissions as $user_permission) {

						$stored_resource_key = $user_permission->getResourceKey();

						if (!$stored_resource_key || strpos($resource_key, $stored_resource_key) === 0) {
							$match_precision = strlen($stored_resource_key) / strlen($resource_key);
							if ($match_precision > $best_precision) {
								$best_matched_permission = $user_permission;
								$best_precision          = $match_precision;
							}
						}
					}
				}

				if (isset($best_matched_permission)) {
					$bit_value = intval($best_matched_permission->getBitValue());
				} else {
					$bit_value = 0;
				}

				$user_permission->setBitValue($bit_value);
			}

			return $user_permission;

		}

		/**
		 * Checks whether or not the user record has a particular permission
		 * granted.  This differs from checkACL in that it checks only explicit
		 * permissions set on the user.
		 *
		 * @param string $resource_key The resource key to check permissions on
		 * @param integer $permission The permission value to check for
		 * @param boolean $inherit Whether or not to inherit permissions if there is no specific permission
		 * @return boolean returns TRUE of the user record has permission, FALSE otherwise
		 */
		public function checkPermission($resource_key, $permission, $inherit = FALSE)
		{
			$user_permission = $this->fetchPermission($resource_key, $inherit);
			$result          = intval($user_permission->getBitValue()) & $permission;
			return ($result == $permission);
		}

		/**
		 * Grants permissions on a particular resource key for the user
		 *
		 * @param string $resource_key The resource key to grant permissions on
		 * @param integer $permission The bit value of permissions to grant
		 * @return void
		 */
		public function grantPermission($resource_key, $permission)
		{
			$user_permission = $this->fetchPermission($resource_key);
			$new_permissions = intval($user_permission->getBitValue()) | $permission;
			$user_permission->setBitValue($new_permissions)->store();
		}

		/**
		 * Revokes permissions on a particular resource key for the user
		 *
		 * @param string $resource_key The resource key to grant permissions on
		 * @param integer $permission The bit value of permissions to revoke
		 * @return void
		 */
		public function revokePermission($resource_key, $permission)
		{
			$user_permission = $this->fetchPermission($resource_key);
			$new_permissions = intval($user_permission->getBitValue()) & ~$permission;
			$user_permission->setBitValue($new_permissions)->store();
		}

		/**
		 * Initializes all static class information for User Model
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
			// Custom ORM Mapping

			// Register the Active Record

			self::register();

			// Extended Information

			self::setInfo(array(
				'allow_mapping'     => TRUE,
				'default_sorting'   => array('id' => 'asc'),
				'password_columns'  => array('login_password'),
				'image_columns'     => array('avatar'),
				'read_only_columns' => array('date_created', 'date_last_accessed')
			));

			// Setup max login attempts

			if (
				isset($config['max_login_attempts']) &&
				$config['max_login_attempts']
			) {
				$max_login_attempts = $config['max_login_attempts'];
			} else {
				$max_login_attempts = self::MAX_LOGIN_ATTEMPTS;
			}

			$pattern = '/(\d+)(\s*\/\s*(\d+)(\s*(days|hours|minutes|seconds)?)?)?/';
			if (!preg_match_all($pattern, $max_login_attempts, $matches)) {
				throw new fProgrammerException (
					"Max login attempts is in an invalid format."
				);
			} else {
				self::$maxLoginAttempts      = ($matches[0][0]) ? $matches[1][0] : self::MAX_LOGIN_ATTEMPTS;
				self::$maxLoginAttemptsTime  = implode(' ', array(
					($matches[2][0]) ? $matches[3][0] : self::MAX_LOGIN_ATTEMPTS_TIME_COUNT,
					($matches[4][0]) ? $matches[5][0] : self::MAX_LOGIN_ATTEMPTS_TIME_MEASURE
				));
			}
		}

		/**
		 * Registers the User model.
		 */
		static public function register()
		{
			parent::register(__CLASS__);
		}

		/**
		 * Sets information for the User model.
		 *
		 * @param mixed $values An associative array of information to set.
		 * @return void
		 */
		static public function setInfo($values)
		{
			return parent::setInfo(__CLASS__, $values);
		}

		/**
		 * Gets the record name for the User class
		 *
		 * @return string The custom or default record translation
		 */
		static public function getRecord()
		{
			return parent::getRecord(__CLASS__);
		}

		/**
		 * Gets the record table name for the User class
		 *
		 * @return string The custom or default record table translation
		 */
		static public function getRecordTable()
		{
			return parent::getRecordTable(__CLASS__);
		}

		/**
		 * Gets the record set name for the User class
		 *
		 * @return string The custom or default record set translation
		 */
		static public function getRecordSet()
		{
			return parent::getRecordSet(__CLASS__);
		}

		/**
		 * Gets the entry name for the User class
		 *
		 * @return string The custom or default entry translation
		 */
		static public function getEntry()
		{
			return parent::getEntry(__CLASS__);
		}

		/**
		 * Gets the the default sorting for the User class
		 *
		 * @return array The default sort array
		 */
		static public function getDefaultSorting()
		{
			return parent::getDefaultSorting(__CLASS__);
		}

		/**
		 * Determines whether or not a column name represents a foreign key
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a foreign key column, FALSE otherwise
		 */
		static public function isFKeyColumn($column)
		{
			return parent::isFKeyColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents an image upload
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is an image upload column, FALSE otherwise
		 */
		static public function isImageColumn($column)
		{
			return parent::isImageColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents a file upload
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a file upload column, FALSE otherwise
		 */
		static public function isFileColumn($column)
		{
			return parent::isFileColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents a password
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a password column, FALSE otherwise
		 */
		static public function isPasswordColumn($column)
		{
			return parent::isPasswordColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents a read-only
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a read-only column, FALSE otherwise
		 */
		static public function isReadOnlyColumn($column)
		{
			return parent::isReadOnlyColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents an auto-increment
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is an auto-increment column, FALSE otherwise
		 */
		static public function isAIColumn($column)
		{
			return parent::isAIColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not the record is allowed to be mapped to
		 * dynamically from entry points or controllers.
		 *
		 * @return boolean TRUE if the record class can be mapped, FALSE otherwise.
		 */
		static public function canMap()
		{
			return parent::canMap(__CLASS__);
		}

		/**
		 * Determines whether the record class only serves as a relationship,
		 * i.e. a many to many table.
		 *
		 * @return boolean TRUE if it is a relationship, FALSE otherwise
		 */
		static public function isRelationship()
		{
			return parent::isRelationship(__CLASS__);
		}


		/**
		 * Creates a new User from a slug and identifier.  The
		 * identifier is optional, but if is provided acts as an additional
		 * check against the validity of the record.
		 *
		 * @param $slug A primary key string representation or "slug" string
		 * @param $identifier The identifier as provided as an extension to the slug
		 * @return fActiveRecord The active record matching the slug
		 */
		static public function createFromSlug($slug, $identifier = NULL)
		{
			return parent::createFromSlug(__CLASS__, $slug, $identifier);
		}

		/**
		 * Authorizes a user and establishes their session by setting their
		 * Access Control Lists and their User Token.
		 *
		 * @param string $login The provided login name or e-mail
		 * @param string $password The provided password
		 * @return void
		 */
		static public function authorize($login, $password)
		{

			$login_success = FALSE;
			$users         = fRecordSet::build('User', array(
				self::LOGIN_FIELD . '=' => $login,
				'status<>'              => 'Disabled'
			));

			// TODO: Known Security Issue:  Login Attempts are only added on the
			// TODO: condition that the user exists.  This means the validity
			// TODO: of a login name could be established by determinine if
			// TODO: the system ever errors with too many login attempts.

			if ($users->count()) {

				$user = $users->fetchRecord();

				$user_login_attempts = UserLoginAttempts::build(array(
					'user_id='        => $user->getId(),
					'remote_address=' => $_SERVER['REMOTE_ADDR']
				), array(
					'date_occurred' => 'desc'
				));

				// Delete all expired login attempts and use the difference of
				// the original recordset

				$user_login_attempts = $user_login_attempts->diff(
					$user_login_attempts->filter(array(
						'getDateOccurred<' => new fTimestamp('-' . self::$maxLoginAttemptsTime)
					))->call('delete')
				);

				// Check if there has been too many login attempts within
				// the login attempts time.

				if ($user_login_attempts->count() >= self::$maxLoginAttempts) {
					throw new fNoRemainingException(
						self::MAX_LOGIN_ATTEMPTS_MSG, self::$maxLoginAttemptsTime
					);
				} else {

					// Add the Login Attempt

					$user_login_attempt = new UserLoginAttempt();
					$user_login_attempt->setUserId($user->getId());
					$user_login_attempt->setRemoteAddress($_SERVER['REMOTE_ADDR']);
					$user_login_attempt->store();

				}

				$login_success = fCryptography::checkPasswordHash($password, $user->getLoginPassword());

			}

			if (!$login_success) {
				throw new fValidationException(self::INVALID_LOGIN_MSG);
			}

			// Successful Login:
			//
			// Remove All Login Attempts, Set the Date Accessed, and Establish Permissions

			$user_login_attempts->call('delete');

			$user->setDateLastAccessed(new fTimestamp())->store();

			$acls         = array();
			$auth_roles   = $user->buildAuthRoles();
			$permissions  = $user->buildUserPermissions()->getRecords();

			foreach ($auth_roles as $auth_role) {
				$auth_role_permissions = $auth_role->buildAuthRolePermissions();
				foreach ($auth_role_permissions as $permission) {
					$resource_key   = $permission->getResourceKey();
					$new_permission = intval($permission->getBitValue());
					if (isset($acls[$resource_key])) {
						$acls[$resource_key] = $acls[$resource_key] | $new_permission;
					} else {
						$acls[$resource_key] = $new_permission;
					}
				}
			}

			foreach($user->buildUserPermissions() as $permission) {
				$resource_key   = $permission->getResourceKey();
				$new_permission = intval($permission->getBitValue());
				if (isset($acls[$resource_key])) {
					$acls[$resource_key] = $acls[$resource_key] | $new_permission;
				} else {
					$acls[$resource_key] = $new_permission;
				}
			}

			fAuthorization::setUserACLs($acls);
			fAuthorization::setUserToken($user->getId());

		}

		/**
		 * Deauthorizes and destroys a user session.
		 *
		 * @param void
		 * @return void
		 */
		static public function deAuthorize()
		{
			fAuthorization::destroyUserInfo();
		}

		/**
		 * Retrieves the logged in user
		 *
		 * @return User The user record
		 */
		static public function retrieveLoggedIn()
		{
			if (self::$logged_in_user == NULL) {
				$token = fAuthorization::getUserToken();
				if ($token !== NULL) {
					try {
						self::$logged_in_user = new User($token);
					} catch (fNotFoundException $e) {
						self::$logged_in_user = NULL;
					}
				}
			}
			return self::$logged_in_user;
		}

		/**
		 * Checks if a user is currently logged in by verifying their session
		 * and determining if their user can be retrieved.
		 *
		 * @param void
		 * @return boolean TRUE if the user has been authenticated FALSE otherwise.
		 */
		static public function checkLoggedIn()
		{
			if (!self::retrieveLoggedIn()) {
				return FALSE;
			}
			return TRUE;
		}

		/**
		 * Checks whether or not the logged in user's access control list
		 * permits an action.
		 *
		 * @param string $resource_key The resource key to check permissions on
		 * @param integer|array $check_permissions The permissions to check for
		 * @return boolean TRUE if the user has permission, FALSE otherwise
		 */
		static public function checkACL($resource_key, $check_permissions)
		{
			$acls = fAuthorization::getUserACLs();

			if (isset($acls[$resource_key])) {
				$best_matched_resource = $resource_key;
			} else {
				$best_precision = -1;
				foreach ($acls as $resource => $permission) {
					if (!$resource || strpos($resource_key, $resource) === 0) {
						$match_precision = strlen($resource) / strlen($resource_key);
						if ($match_precision > $best_precision) {
							$best_matched_resource = $resource;
							$best_precision        = $match_precision;
						}
					}
				}
			}

			if (isset($best_matched_resource)) {
				$permissions = $acls[$best_matched_resource];
				if (!is_array($check_permissions)) {
					$check_permissions = array($check_permissions);
				}
				foreach ($check_permissions as $check_permission) {
					if (($permissions & $check_permission) == $check_permission) {
						return TRUE;
					}
				}
			}
			return FALSE;
		}
	}
