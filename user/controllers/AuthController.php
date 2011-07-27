<?php

	/**
	 * A basic Authorization/Authentication controller which provides login and
	 * logout actions for other controllers or as entry points.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class AuthController extends Controller
	{

		const LOGOUT_SUCCESS_MSG      = 'You have successfully logged out';
		const NOT_LOGGED_IN_MSG       = 'You are not logged in silly!';
		
		const DEFAULT_LOGIN_VIEW      = 'pages/login.php';

		const DEFAULT_HOST_CONTROLLER = 'PagesController';
		const DEFAULT_HOST_METHOD     = 'notAuthorized';

		// Information about the controller state

		/**
		 * Whether or not we're going to use HTTP Authentication
		 *
		 * @static
		 * @access private
		 * @var boolean
		 */
		static private $usingHTTPAuth = FALSE;

		/**
		 * The URL to redirect to upon login success, if not started from a
		 * previous request.
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $loginSuccessURL = NULL;
		
		/**
		 * The view to use for non HTTP authorization logins.  This is
		 * expected to be HTML
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $loginView = NULL;
		
		/**
		 * The host controller class
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $hostController = NULL;
		
		/**
		 * The host controller method
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $hostMethod = NULL;

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
			parent::__init($config);
		
			self::$loginSuccessURL = isset($config['login_success_url'])
				? $config['login_success_url']
				: '/';

			$request_format = self::getRequestFormat();

			if (isset($config['http_auth_formats'])) {
				self::$usingHTTPAuth = is_array($config['http_auth_formats'])
					? in_array($request_format, $config['http_auth_formats'])
					: ($request_format == $config['http_auth_formats']);
			}

			self::$loginView = isset($config['login_view'])
				? $config['login_view']
				: self::DEFAULT_LOGIN_VIEW;
				
			self::$hostController = isset($config['host_controller'])
				? $config['host_controller']
				: self::DEFAULT_HOST_CONTROLLER;
				
			self::$hostMethod = isset($config['host_method'])
				? $config['host_method']
				: self::DEFAULT_HOST_METHOD;

			return TRUE;
		}

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
				fMessaging::create(
					self::MSG_TYPE_SUCCESS,
					$target,
					self::LOGOUT_SUCCESS_MSG
				);
			} else {
				fMessaging::create(
					self::MSG_TYPE_ERROR,
					$target,
					self::NOT_LOGGED_IN_MSG
				);
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
			$is_entry_point = self::checkEntryAction(__CLASS__, __FUNCTION__);
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
						$auth_string = $_SERVER['HTTP_AUTHORIZATION'];
						$auth_string = substr($auth_string, 6);
						$auth_string = base64_decode($auth_string);
						$login_info  = explode(':', $decoded_auth_string);
						$username    = $login_info[0];
						$password    = $login_info[1];
					}

				} else {

					fMessaging::create($message_type, $target, $message);
					fURL::redirect(Moor::linkTo($target));
				}

			} elseif (fRequest::isPost()) {

				$username = fRequest::get('username', 'string', NULL);
				$password = fRequest::get('password', 'string', NULL);
			}

			// If we have been provided a username, try to login

			if (isset($username)) {
				try {

					User::authorize($username, $password);

					$redirect_url = fAuthorization::getRequestedURL(TRUE);
					if (empty($redirect_url)) {
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

				$route = iw::makeTarget(
					self::$hostController,
					self::$hostMethod
				);

				fMessaging::create($message_type, $route, $message);

				self::exec($route);

			} else {

				fMessaging::create($message_type, $target, $message);

				$host = new self::$hostController();

				$host -> view
					  -> add    ('contents', self::$loginView)
					  -> pack   ('id',       'login')
					  -> push   ('title',    'Login')
					  -> render ();
			}
		}
	}
