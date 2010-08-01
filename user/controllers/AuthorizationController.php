<?php

	/**
	 * An authorization controller which provides login and logout methods as
	 * well as additional methods for verifying authorization.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class AuthorizationController extends Controller
	{

		const LOGOUT_SUCCESS_MSG         = 'You have successfully logged out';
		const NOT_LOGGED_IN_MSG          = 'You are not logged in silly!';

		// Information about the controller state

		static private $username         = NULL;
		static private $password         = NULL;
		static private $usingHTTPAuth    = FALSE;
		static private $authHeaderSent   = FALSE;


		// Where to redirect a user upon successful login

		static private $loginSuccessURL  = NULL;

		/**
		 * Destroys a user's session and removes all authorization information
		 * related to them.
		 *
		 * @param void
		 * @return void
		 */
		static public function logout()
		{

			$target = iw::makeTarget(__CLASS__, 'login');

			if (User::retrieveLoggedIn()) {
				User::deAuthorize();
				fMessaging::create('success', $target, self::LOGOUT_SUCCESS_MSG);
			} else {
				fMessaging::create('error',   $target, self::NOT_LOGGED_IN_MSG);
			}
			fURL::redirect(Moor::LinkTo(iw::makeTarget(__CLASS__, 'login')));

		}

		/**
		 * Logs a user in and if successful redirects them apporpriately.
		 *
		 * @param void
		 * @return void
		 */
		static public function login()
		{
			// Attempt to get our username and password

			if (isset($_SERVER['PHP_AUTH_USER'])) {
				$username = $_SERVER['PHP_AUTH_USER'];
				$password = $_SERVER['PHP_AUTH_PW'];

			} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
				$auth_string = substr($_SERVER['HTTP_AUTHORIZATION'], 6);
				$auth_string = base64_decode($auth_string);
				$login_info  = explode(':', $decoded_auth_string);
				$username    = $login_info[0];
				$password    = $login_info[1];

			} elseif (fRequest::isPost()) {
				$username = fRequest::get('username', 'string?');
				$password = fRequest::get('password', 'string?');
			}

			// If we have been provided a username, try to login

			if (isset($username)) {
				try {

					User::authorize($username, $password);

					if (!($redirect_url = fAuthorization::getRequestedURL(TRUE))) {
						$redirect_url = self::$loginSuccessURL;
					}

					fURL::redirect($redirect_url);

				} catch (fExpectedException $e) {
					$message_type = self::MSG_TYPE_ERROR;
					$message      = $e->getMessage();
				}
			}

			if (self::$authHeaderSent) {

				echo 'death'; exit();

			} else {
				if (isset($message)) {
					$target = iw::makeTarget(__CLASS__, __FUNCTION__);
					fMessaging::create($message_type, $target, $message);
				}

				$page = new PagesController();

				$page->view
					-> add  ('primary_section', 'pages/login.php')
					-> pack ('id',              'login')
					-> push ('title',           'Login')
					-> render();
			}
		}

		/**
		 * Initializes the AuthedController
		 *
		 * @param void
		 * @return void
		 */
		static public function __init($config)
		{
			if (isset($config['login_success_url'])) {
				self::$loginSuccessURL = $config['login_success_url'];
			} else {
				self::$loginSuccessURL = '/';
			}

			if (isset($config['http_auth_formats'])) {
				self::$usingHTTPAuth = in_array(
					self::getRequestFormat(),
					$config['http_auth_formats']
				);
			}

			// Establish permission definitions

			$every_permission  = 0;

			foreach (AuthActions::build() as $auth_action) {
				$action_name      = $auth_action->getName();
				$action_value     = intval($auth_action->getBitValue());
				$every_permission = $every_permission | $action_value;

				define(AuthAction::makeDefinition($action_name), $action_value);
			}
			define('PERM_ALL', $every_permission);
		}

		/**
		 * Checks if a user is logged in.  This should be included at the top
		 * of pages which require a user to be logged in in order to view.  In
		 * the event they are not logged in this method will determine the
		 * appropriate action to get them logged in.
		 *
		 * @param void
		 * @return void
		 */
		static protected function requireLoggedIn()
		{
			if (!User::checkLoggedIn()) {

				fAuthorization::setRequestedURL(fURL::getWithQueryString());

				if (self::$usingHTTPAuth) {

					header(implode(' ', array(
						'WWW-Authenticate:',             // Header
						'Basic',                         // Authentication Type
						'realm="inKWell Control Panel"', // Realm
					)));

					self::$authHeaderSent = TRUE;
					self::triggerNotAuthorized();

				} else {

					$message_type = self::MSG_TYPE_ALERT;
					$target       = iw::makeTarget(__CLASS__, 'login');
					$message      = self::NOT_AUTHORIZED_MSG;

					fMessaging::create($message_type, $target, $message);
					fURL::redirect(Moor::linkTo($target));
				}
			}
		}

		/**
		 * Requires that the user's access control list permits an action and
		 * triggers forbidden if not.
		 *
		 * @param string $req_resource The resource to require permissions on
		 * @param integer $req_permissions The permissions to require
		 * @return void
		 */
		static protected function requireACL($req_resource, $req_permissions)
		{
			if (!User::checkACL($req_resource, $req_permissions)) {
				self::triggerForbidden();
			}
		}

	}


