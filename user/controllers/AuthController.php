<?php

	/**
	 * A basic Authorization/Authentication controller which provides login and
	 * logout actions for other controllers or as entry points.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class AuthController extends Controller
	{

		const LOGOUT_SUCCESS_MSG         = 'You have successfully logged out';
		const NOT_LOGGED_IN_MSG          = 'You are not logged in silly!';

		// Information about the controller state

		static private $username         = NULL;
		static private $password         = NULL;
		static private $usingHTTPAuth    = FALSE;

		// Where to redirect a user upon successful login

		static private $loginSuccessURL  = NULL;

		/**
		 * Destroys a user's session and removes all authorization information
		 * related to them.  This can be used as an entry point, but essentially
		 * just redirects to the login entry point after destroying the session.
		 *
		 * @param void
		 * @return void
		 */
		static public function logout()
		{

			$target = iw::makeTarget(__CLASS__, 'login');

			if (User::retrieveLoggedIn()) {
				User::deAuthorize();
				fMessaging::create(self::MSG_TYPE_SUCCESS, $target, self::LOGOUT_SUCCESS_MSG);
			} else {
				fMessaging::create(self::MSG_TYPE_ERROR, $target, self::NOT_LOGGED_IN_MSG);
			}
			fURL::redirect(Moor::LinkTo(iw::makeTarget(__CLASS__, 'login')));

		}

		/**
		 * Attempts to log a user in.  If this action is being used as an
		 * entry point it will show a login page, if not, depending on whether
		 * or HTTP Authentication is enabled on the current request format it
		 * will attempt to use that, or redirect to this entry point.
		 *
		 * @param void
		 * @return void
		 */
		static public function login()
		{

			$is_entry_point = self::isEntryAction(__CLASS__, __FUNCTION__);
			$target         = iw::makeTarget(__CLASS__, __FUNCTION__);

			// Receive any original messages

			$message_type   = self::MSG_TYPE_ERROR;
			$message        = fMessaging::retrieve($message_type, $target);

			if (!$is_entry_point) {

				fAuthorization::setRequestedURL(fURL::getWithQueryString());

				if (self::$usingHTTPAuth) {

					header(implode(' ', array(
						'WWW-Authenticate:',             // Header
						'Basic',                         // Authentication Type
						'realm="inKWell Control Panel"', // Realm
					)));

					if (isset($_SERVER['PHP_AUTH_USER'])) {
						$username = $_SERVER['PHP_AUTH_USER'];
						$password = $_SERVER['PHP_AUTH_PW'];

					} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
						$auth_string = substr($_SERVER['HTTP_AUTHORIZATION'], 6);
						$auth_string = base64_decode($auth_string);
						$login_info  = explode(':', $decoded_auth_string);
						$username    = $login_info[0];
						$password    = $login_info[1];
					}

				} else {

					$message_type = self::MSG_TYPE_ALERT;
					$target       = iw::makeTarget(__CLASS__, 'login');
					$message      = self::NOT_AUTHORIZED_MSG;

					fMessaging::create($message_type, $target, $message);
					fURL::redirect(Moor::linkTo($target));
				}

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

			// Take different courses of action depending on if we're the entry
			// point, either re-route the request or handle it.

			if (!$is_entry_point) {

				$route = iw::makeTarget('PagesController', 'notAuthorized');

				fMessaging::create($message_type, $route, $message);

				self::exec($route);

			} else {

				fMessaging::create($message_type, $target, $message);

				$page = new PagesController();

				$page->view
					-> add  ('primary_section', 'pages/login.php')
					-> pack ('id',              'login')
					-> push ('title',           'Login')
					-> render();
			}
		}

		/**
		 * Initializes the AuthController.  The primary tasks involved here are
		 * as follows:
		 *
		 * 1) Establish a any configured login success URL
		 * 2) Determine whether or not we should attempt to use HTTP Auth
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

			if (
				isset($config['http_auth_formats'])    &&
				is_array($config['http_auth_formats'])
			) {
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

	}


