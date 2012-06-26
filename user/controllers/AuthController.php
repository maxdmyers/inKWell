<?php

	/**
	 * A basic Authorization/Authentication controller which provides login and
	 * logout actions for other controllers or as entry points.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class AuthController extends Controller
	{

		const LOGOUT_SUCCESS_MSG = 'You have successfully logged out';
		const NOT_LOGGED_IN_MSG  = 'You are not logged in silly!';
		const DEFAULT_HOST       = NULL;

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
		 * The host target action
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $host = NULL;

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
		static public function __init(array $config = array(), $element = NULL)
		{
			self::$loginSuccessURL = isset($config['login_success_url'])
				? $config['login_success_url']
				: '/';

			self::$usingHTTPAuth = isset($config['non_http_auth_formats'])
				? !in_array(Request::getFormat(), $config['non_http_auth_formats'])
				: TRUE;

			self::$host = isset($config['host'])
				? $config['host']
				: self::DEFAULT_HOST;

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

				$message      = fText::compose(self::LOGOUT_SUCCESS_MSG);
				$message_type = self::MSG_TYPE_SUCCESS;
			} else {
				$message      = fText::compose(self::NOT_LOGGED_IN_MSG);
				$message_type = self::MST_TYPE_ERROR;
			}

			fMessaging::create($message_type, $target, $message);
			self::redirect(iw::makeTarget(__CLASS__, 'login'));
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
			$self         = iw::makeTarget(__CLASS__, __FUNCTION__);
			$message_type = self::MSG_TYPE_ERROR;
			$message      = fMessaging::retrieve($message_type, $self);

			if (!self::checkEntryAction(__CLASS__, __FUNCTION__)) {

				fAuthorization::setRequestedURL(fURL::getWithQueryString());

				if (self::$usingHTTPAuth) {
					//
					// We're going to handle authorization via HTTP protocol if it's enabled
					//
					header(implode(' ', array(
						'WWW-Authenticate:',          // Header
						'Basic',                      // Authentication Type
						'realm="Site Authorization"', // Realm
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
					//
					// Otherwise we'll redirect to our login route
					//
					fMessaging::create($message_type, $self, $message);
					self::redirect($self);
				}

			} elseif (Request::checkMethod('post')) {
				$username = Request::get('username', 'string', NULL);
				$password = Request::get('password', 'string', NULL);
			}
			//
			// If we have been provided a username, try to login
			//
			if (isset($username)) {
				try {
					// User::authorize($username, $password);
					throw new fExpectedException('wtf');
					self::redirect(
						($url = fAuthorization::getRequestURLs(TRUE))
							? $url
							: self::$loginSuccessURL
					);
				} catch (fExpectedException $e) {
					$message_type = self::MSG_TYPE_ERROR;
					$message      = $e->getMessage();
				}
			}

			fMessaging::create($message_type, $self, $message);
			return self::exec(self::$host, self::checkEntryAction(__CLASS__, __FUNCTION__));
		}
	}
