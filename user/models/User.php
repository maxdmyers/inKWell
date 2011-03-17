<?php

	/**
	 * User model which provides direct access to a single user record using
	 * an instantiated instance, as well as user authorization methods.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell::Extensions::Auth
	 */
	class User extends ActiveRecord
	{

		const INVALID_LOGIN_MSG                = 'Username and/or password are invalid.';
		const INVALID_SESSION_MSG              = 'You have been logged out for security reasons.';
		const MAX_LOGIN_ATTEMPTS_MSG           = 'You have attempted to login too many times, please try again in %s.';
		const REGEX_MAX_LOGIN_ATTEMPTS         = '/(\d+)(\s*\/\s*(\d+)(\s*(days|hours|minutes|seconds)?)?)?/';

		const MAX_LOGIN_ATTEMPTS               = 5;
		const MAX_LOGIN_ATTEMPTS_TIME_COUNT    = 30;
		const MAX_LOGIN_ATTEMPTS_TIME_MEASURE  = 'minutes';
		const DEFAULT_ALLOW_EMAIL_LOGIN        = TRUE;

		/**
		 * Number of login attempts which can occur in our max login attempts
		 * timeframe
		 *
		 * @static
		 * @access private
		 * @var integer
		 */
		static private $maxLoginAttempts = NULL;

		/**
		 * String representation of timeframe in which the maximum number
		 * of login attempts can occur.
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $maxLoginAttemptsTime = NULL;

		/**
		 * Whether or not we allow logging in via E-mail Address
		 *
		 * @static
		 * @access private
		 * @var boolean
		 */
		static private $allowEmailLogin = NULL;

		/**
		 * The currently logged in user
		 *
		 * @static
		 * @access protected
		 * @var User
		 */
		static protected $logged_in_user = NULL;

		/**
		 * Obfuscates the auth hash in standard text for HTML encoded
		 * output.
		 *
		 * @access public
		 * @param void
		 * @return string The obfuscated login password
		 */
		public function encodeLoginPassword()
		{
			return fHTML::encode('< Encrypted Password >');
		}

		/**
		 * Prepares and returns the login password for HTML views by obfuscating
		 * it.
		 *
		 * @access public
		 * @param void
		 * @return string The obfuscated auth hash
		 */
		public function prepareLoginPassword()
		{
			return '<em>' . $this->encodeLoginPassword() . '</em>';
		}

		/**
		 * Rebuilds a user's ACL and marks it as rebuilt in the database
		 *
		 * @access private
		 * @param UserSession $user_session The user session to rebuild for
		 * @return void
		 */
		private function rebuildACL(UserSession $user_session)
		{
			$acls        = array();
			$auth_roles  = $user->buildAuthRoles();
			$permissions = $user->buildUserPermissions();

			foreach ($auth_roles as $auth_role) {
				$auth_role_permissions = $auth_role->buildAuthRolePermissions();
				foreach ($auth_role_permissions as $permission) {
					$record_name  = $permission->getRecordName();
					$resource_key = $permission->getResourceKey();
					$column       = $permission->getColumn();
					if (isset($acls[$record_name][$resource_key][$column])) {
						$acl = &$acls[$record_name][$resource_key][$column];
						$acl = $acl | intval($permission->getBitValue());
					} else {
						$acls[$record_name][$resource_key][$column] = intval(
							$permission->getBitValue()
						);
					}
				}
			}

			foreach ($permissions as $permission) {
				$record_name  = $permission->getRecordName();
				$resource_key = $permission->getResourceKey();
				$column       = $permission->getColumn();
				if (isset($acls[$record_name][$resource_key][$column])) {
					$acl = &$acls[$record_name][$resource_key][$column];
					$acl = $acl | intval($permission->getBitValue());
				} else {
					$acls[$record_name][$resource_key][$column] = intval(
						$permission->getBitValue()
					);
				}
			}

			fAuthorization::setUserACLs($acls);
			$user_session->setId(session_id);
			$user_session->store();
		}

		/**
		 * Initializes all static class information for User Model
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
			if (!parent::__init($config, __CLASS__)) {
				return FALSE;
			}

			// Setup max login attempts

			if (
				isset($config['max_login_attempts']) &&
				$config['max_login_attempts']
			) {
				$max_login_attempts = $config['max_login_attempts'];
			} else {
				$max_login_attempts = self::MAX_LOGIN_ATTEMPTS;
			}

			$pattern = '';
			if (
				!preg_match_all(
					self::REGEX_MAX_LOGIN_ATTEMPTS,
					$max_login_attempts,
					$matches
				)
			) {
				throw new fProgrammerException (
					"Max login attempts is in an invalid format."
				);

			} else {
				self::$maxLoginAttempts = ($matches[0][0])
					? $matches[1][0]
					: self::MAX_LOGIN_ATTEMPTS;

				self::$maxLoginAttemptsTime = implode(' ', array(
					($matches[2][0])
						? $matches[3][0]
						: self::MAX_LOGIN_ATTEMPTS_TIME_COUNT,
					($matches[4][0])
						? $matches[5][0]
						: self::MAX_LOGIN_ATTEMPTS_TIME_MEASURE
				));
			}

			self::$allowEmailLogin = isset($config['allow_email_login'])
				? $config['allow_email_login']
				: self::DEFAULT_ALLOW_EMAIL_LOGIN;

			self::retrieveLoggedIn();

			return TRUE;
		}

		/**
		 * Gets the record name for the User class
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return string The custom or default record translation
		 */
		static public function getRecordName()
		{
			return parent::getRecordName(__CLASS__);
		}

		/**
		 * Gets the record table name for the User class
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return string The custom or default record table translation
		 */
		static public function getRecordTable()
		{
			return parent::getRecordTable(__CLASS__);
		}

		/**
		 * Gets the record set name for the User class
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return string The custom or default record set translation
		 */
		static public function getRecordSet()
		{
			return parent::getRecordSet(__CLASS__);
		}

		/**
		 * Gets the entry name for the User class
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return string The custom or default entry translation
		 */
		static public function getEntry()
		{
			return parent::getEntry(__CLASS__);
		}

		/**
		 * Gets the order for the User class
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return array The default sort array
		 */
		static public function getOrder()
		{
			return parent::getOrder(__CLASS__);
		}

		/**
		 * Determines whether the record class only serves as a relationship,
		 * i.e. a many to many table.
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return boolean TRUE if it is a relationship, FALSE otherwise
		 */
		static public function isRelationship()
		{
			return parent::isRelationship(__CLASS__);
		}

		/**
		 * Creates a new User from a slug and identifier.  The identifier is
		 * optional, but if is provided acts as an additional check against the
		 * validity of the record.
		 *
		 * @static
		 * @access public
		 * @param $slug A primary key string representation or "slug" string
		 * @param $identifier The identifier as provided as an extension to the slug
		 * @return fActiveRecord The active record matching the slug
		 */
		static public function createFromSlug($slug, $identifier = NULL)
		{
			return parent::createFromSlug(__CLASS__, $slug, $identifier);
		}

		/**
		 * Creates a new User from a provided resource key.
		 *
		 * @static
		 * @access public
		 * @param string $resource_key A JSON encoded primary key
		 * @return fActiveRecord The active record matching the resource key
		 *
		 */
		static public function createFromResourceKey($resource_key)
		{
			return parent::createFromResourceKey(__CLASS__, $resource_key);
		}

		/**
		 * Authorizes a user and establishes their session by setting their
		 * Access Control Lists and their User Token.
		 *
		 * @static
		 * @access public
		 * @param string $username The provided username or e-mail
		 * @param string $password The provided password
		 * @return void
		 */
		static public function authorize($username, $password)
		{
			$user    = NULL;
			$users   = Users::build(array(
				'username=' => $username,
				'status<>'  => 'Disabled'
			));

			if (count($users)) {
				$user = $users->getRecord(0);
			} elseif (self::$allowEmailLogin) {
				$email_addresses = UserEmailAddresses::build(array(
					'email_address='      => $username,
					'auth.users.status<>' => 'Disabled'
				));

				if (count($email_addresses)) {
					$user = $email_addresses->getRecord(0)->createUser();
				}
			}

			$user_id = ($user)
				? $user->getId()
				: NULL;

			// Build existing login attempts for this user from this address

			$login_attempts = LoginAttempts::build(array(
				'user_id='        => $user_id,
				'remote_address=' => $_SERVER['REMOTE_ADDR']
			), array(
				'date_occurred'   => 'desc'
			));

			// Get our expiration time

			$expiration = new fTimestamp('-' . self::$maxLoginAttemptsTime);

			// Delete all expired login attempts and use the difference of the
			// original recordset

			$expired_attempts = $login_attempts->filter(array(
				'getDateOccurred<' => $expiration
			))->call('delete');

			$login_attempts   = $login_attempts->diff($expired_attempts);


			// Check if there has been too many login attempts within the login
			// attempts time.

			if ($user_login_attempts->count() >= self::$maxLoginAttempts) {
				throw new fNoRemainingException(
					fText::compose(
						self::MAX_LOGIN_ATTEMPTS_MSG,
						self::$maxLoginAttemptsTime
					)
				);
			} else {

				// Add the Login Attempt

				$user_login_attempt = new LoginAttempt();
				$user_login_attempt->setUserId($user_id);
				$user_login_attempt->setRemoteAddress($_SERVER['REMOTE_ADDR']);
				$user_login_attempt->store();

			}

			$login_success = ($user && ($hash = $user->getLoginPassword()))
				? fCryptography::checkPasswordHash($password, $hash)
				: FALSE;

			// Unsuccessful Login:

			if (!$login_success) {
				throw new fValidationException(
					fText::compose(self::INVALID_LOGIN_MSG)
				);
			}

			// Successful Login:
			//
			// Remove All Login Attempts, Set the Date Accessed, and Establish
			// Permissions

			$user_login_attempts->call('delete');

			// Set the user token, last access data, and rebuild the ACL

			fAuthorization::setUserToken($user->getId());

			$user_session = new UserSession();
			$user_session->setId(session_id());
			$user_session->setLastActivity(new fTimestamp());
			$user_session->setRemoteAddress($_SERVER['REMOTE_ADDR']);
			$user_session->associateUser($user);

			$user->setDateLastAccessed(new fTimestamp());
			$user->setLastAccessedFrom($_SERVER['REMOTE_ADDR']);
			$user->rebuildACL($user_session);
			$user->store();
		}

		/**
		 * Deauthorizes and destroys the user session of the currently logged
		 * in user.
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return void
		 */
		static public function deAuthorize()
		{
			fAuthorization::destroyUserInfo();
			return TRUE;
		}

		/**
		 * Retrieves the logged in user
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return User The currently logged in user record
		 */
		static public function retrieveLoggedIn()
		{
			if (self::$logged_in_user == NULL) {
				$token = fAuthorization::getUserToken();
				if ($token !== NULL) {
					try {

						self::$logged_in_user = new User($token);

						// Update their session

						$user_session = new UserSession(session_id());
						$user_address = $user_session->getRemoteAddress();
						$user_session->setLastActivity(fTimestamp());
						$user_session->store();

						if ($user_address != $_SERVER['REMOTE_ADDR']) {
							self::$logged_in_user = NULL;
							throw new fValidationException(
								fText::compose(self::INVALID_SESSION_MSG)
							);
						}

						if ($user_session->getRebuildAcl()) {
							self::$logged_in_user->rebuildACL($user_session);
						}

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
		 * @static
		 * @access public
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
	}
