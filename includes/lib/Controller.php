<?php

	/**
	 * A base controller class which provides facilities for triggering various
	 * responses, and building higher level controllers.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell
	 */
	class Controller extends MoorAbstractController implements inkwell
	{

		const SUFFIX                      = __CLASS__;

		const DEFAULT_CONTROLLER_ROOT     = 'user/controllers';

		const DEFAULT_REQUEST_FORMAT      = 'html';
		const DEFAULT_AJAX_REQUEST_FORMAT = 'json';

		const DEFAULT_SITE_SECTION        = 'default';
		const DEFAULT_SITE_TITLE          = 'inKWell Site';
		const DEFAULT_USE_SSL             = FALSE;

		const MSG_TYPE_ERROR              = 'error';
		const MSG_TYPE_ALERT              = 'alert';
		const MSG_TYPE_SUCCESS            = 'success';

		/**
		 * The controller's view object
		 *
		 * @access protected
		 * @var View
		 */
		protected $view = NULL;

		/**
		 * The path from which relative controllers are loaded
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $controllerRoot = NULL;

		/**
		 * The path to the controllers within a section
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $basePath = NULL;

		/**
		 * An array of error handlers used with triggerError()
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $errors = array();

		/**
		 * A list of default accept mime types
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $defaultAcceptTypes = array();

		/**
		 * The default request format for standard requests
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $defaultRequestFormat = NULL;

		/**
		 * The default request format for AJAX requests
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $defaultAjaxRequestFormat = NULL;

		/**
		 * An array of available site sections and related data
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $siteSections = array();

		/**
		 * The cached baseURL for the request, based on sitesections
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $baseURL = NULL;

		/**
		 * The request path for the request as sent by the client
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $requestPath = NULL;

		/**
		 * The current request format as sent by the client
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $requestFormat = NULL;

		/**
		 * The Content-Type to send on sendHeader()
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $contentType = NULL;

		/**
		 * Whether or not Content-Type headers were sent
		 *
		 * @static
		 * @access private
		 * @var boolean
		 */
		static private $typeHeadersSent = FALSE;

		/**
		 * Builds a new controller by assigning it a local view and running
		 * prepare if it exists.  Only static methods on controllers can
		 * instantiate a new controller object, and all standard __construct()
		 * functionality should be moved to prepare().
		 *
		 * @final
		 * @access protected
		 * @param void
		 * @return void
		 */
		final protected function __construct()
		{
			$this->view = new View();

			if (method_exists($this, 'prepare')) {
				$prepare_callback = array($this, 'prepare');
				$arguments        = func_get_args();
				call_user_func_array($prepare_callback, $arguments);
			}
		}

		/**
		 * Prepares a new controller by establishing any shared object
		 * information
		 *
		 * @access protected
		 * @param void
		 * @return void
		 */
		protected function prepare()
		{
			$section = self::getBaseURL();

			$title   = (isset(self::$siteSections[$section]['title']))
				? self::$siteSections[$section]['title']
				: self::DEFAULT_SITE_TITLE;

			$use_ssl = (isset(self::$siteSections[$section]['use_ssl']))
				? self::$siteSections[$section]['use_ssl']
				: self::DEFAULT_USE_SSL;

			// Redirect to https:// if required for the section

			if ($use_ssl && empty($_SERVER['HTTPS'])) {
				$domain     = fURL::getDomain();
				$request    = fURL::getWithQueryString();
				$ssl_domain = str_replace('http://', 'https://', $domain);
				fURL::redirect($ssl_domain . $request);
			}

			self::sendHeader();

			$this->view
				-> load (self::getRequestFormat() . '.php')
				-> push ('title',   $title);
		}


		/**
		 * Matches whether or not a given class name is a potential
		 * Controller
		 *
		 * @static
		 * @access public
		 * @param string $class The name of the class to check
		 * @return boolean TRUE if it matches, FALSE otherwise
		 */
		static public function __match($class) {
			return preg_match('/(.*)Controller/', $class);
		}

		/**
		 * Initializes the global controller namely by establishing error
		 * handlers, headers, and messages.
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return boolean TRUE if the initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array(), $element = NULL)
		{

			self::$controllerRoot = implode(DIRECTORY_SEPARATOR, array(
				iw::getRoot(),
				($root_directory = iw::getRoot($element))
					? $root_directory
					: self::DEFAULT_CONTROLLER_ROOT
			));

			self::$controllerRoot = new fDirectory(self::$controllerRoot);

			// Configure default accept types

			if (isset($config['default_accept_types'])) {
				self::$defaultAcceptTypes = $config['default_accept_types'];
			} else {
				self::$defaultAcceptTypes = array(
					'text/html',
					'application/json',
					'application/xml'
				);
			}

			// Configure default request format

			self::$defaultRequestFormat = self::DEFAULT_REQUEST_FORMAT;

			if (isset($config['default_request_format'])) {
				self::$defaultRequestFormat = strtolower(
					$config['default_request_format']
				);
			}

			// Configure default AJAX request format

			self::$defaultAjaxRequestFormat = self::DEFAULT_AJAX_REQUEST_FORMAT;

			if (isset($config['default_ajax_request_format'])) {
				self::$defaultAjaxRequestFormat = strtolower(
					$config['default_ajax_request_format']
				);
			}

			// Build our site sections

			$controller_configs = iw::getConfigsByType('controller');
			array_unshift($controller_configs, iw::getConfig('controller'));

			foreach ($controller_configs as $controller_config) {

				if (isset($controller_config['sections'])) {
					if (!is_array($controller_config['sections'])) {
						throw new fProgrammerException (
							'Site sections must be an array of base urls ' .
							'(keys) to titles (values)'
						);
					}

					self::$siteSections = array_merge(
						self::$siteSections,
						$controller_config['sections']
					);
				}

			}

			// Configure errors and error handlers

			if (isset($config['errors'])) {
				if (!is_array($config['errors'])) {
					throw new fProgrammerException (
						'Error configuration requires an array.'
					);
				}
				foreach ($config['errors'] as $error => $info) {
					if (!is_array($info)) {
						throw new fProgrammerException (
							'Error %s must be configured as an array.',
							$error
						);
					}

					$handler = isset($info['handler'])
						? $handler = $info['handler']
						: NULL;

					$header = isset($info['header'])
						? $header = $info['header']
						: NULL;

					$message = isset($info['message'])
						? $message = $info['message']
						: NULL;


					self::setError($error, $handler, $header, $message);
				}
			}
		}

		/**
		 * Gets format mime types for selected or all request formats
		 *
		 * @static
		 * @access protected
		 * @param string $format The particular format type to get
		 * @return array The format mime types for the requested format
		 */
		static protected function getFormatTypes($format = NULL)
		{
			$format_types = array(

				'html' => array(
					'text/html'
				),

				'json' => array(
					'application/json',
					'application/x-javascript'
				),

				'xml'  => array(
					'application/xml'
				),

				'php'  => array(
					'application/octet-stream'
				),

				'jpg'  => array(
					'image/jpeg'
				),

				'gif'  => array(
					'image/gif'
				),

				'png'  => array(
					'image/png'
				),

				'css'  => array(
					'text/css'
				),

				'js'   => array(
					'application/x-javascript'
				)
			);

			if ($format === NULL) {
				return $format_types;
			} elseif (isset($format_types[$format])) {
				return $format_types[$format];
			} else {
				return array();
			}
		}

		/**
		 * Sends the appropriate headers.  Headers will be determined by
		 * the use of the acceptTypes() method.  If it has not been run prior
		 * to this method, it will be run with configured default accept types.
		 *
		 * @static
		 * @access protected
		 * @param array $headers Additional headers aside from content type to send
		 * @return void
		 */
		static protected function sendHeader($headers = array())
		{
			if (!self::$typeHeadersSent) {

				if (!self::$contentType) {
					// The below block implies accepTypes() was never called
					if ($format = self::getRequestFormat()) {
						$format_types      = self::getFormatTypes($format);
						self::$contentType = ($format_types)
							? array_shift($format_types)
							: 'text/html';
					} else {
						self::acceptTypes();
					}
				}

				header('Content-Type: ' . self::$contentType);
				foreach ($headers as $header => $value) {
					header($header . ': ' . $value);
				}
				self::$typeHeadersSent = TRUE;
			}
		}

		/**
		 * Determines whether or not we should accept the request based on
		 * the mime type accepted by the user agent.  If no array or an empty
		 * array is passed the configured default accept types will be used.
		 * If the request_format is provided in the request and the list of
		 * acceptable types does not support the provided accept headers a
		 * not_found error will be triggerd.  If no request_format is provided
		 * in the request and the list of acceptable types does not support the
		 * provided accept headers the method will trigger a 'not_acceptable'
		 * error.
		 *
		 * @static
		 * @access protected
		 * @param array $accept_types An array of acceptable mime types
		 * @return mixed The best type upon request
		 */
		static protected function acceptTypes(array $accept_types = array())
		{
			if (!count($accept_types)) {
				$accept_types = self::$defaultAcceptTypes;
			}

			// The below mapping is used solely to normalize the request
			// format to retrieve the above listed format accept types

			switch ($request_format = self::getRequestFormat()) {
				case 'htm':
				case 'html':
					$request_format_types = self::getFormatTypes('html');
					break;
				case 'json':
					$request_format_types = self::getFormatTypes('json');
					break;
				case 'xml':
					$request_format_types = self::getFormatTypes('xml');
					break;
				case 'php':
					$request_format_types = self::getFormatTypes('php');
					break;
				case 'jpg':
				case 'jpeg':
					$request_format_types = self::getFormatTypes('jpg');
					break;
				case 'gif':
					$request_format_types = self::getFormatTypes('gif');
					break;
				case 'png':
					$request_format_types = self::getFormatTypes('png');
					break;
				default:
					$request_format_types = NULL;
					break;
			}

			$best_accept_types = ($request_format_types)
				? array_intersect($accept_types, $request_format_types)
				: $accept_types;

			if (count($best_accept_types)) {
				$best_type = fRequest::getBestAcceptType($best_accept_types);
				if ($best_type !== FALSE) {
					if (!self::$requestFormat) {
						foreach(self::getFormatTypes() as $format => $types) {
							if (in_array($best_type, $types)) {
								self::$requestFormat = $format;
								break;
							}
						}
					}
					return (self::$contentType = $best_type);
				}
				self::triggerError('not_acceptable');
			} else {
				self::triggerError('not_found');
			}
		}

		/**
		 * Determines whether or not we should accept the request based on
		 * the languages accepted by the user agent.
		 *
		 * @static
		 * @access protected
		 * @param array $language An array of acceptable languages
		 * @return mixed The method will trigger a 'not_accepted' error on failure, will return the best type upon success.
		 */
		static protected function acceptLanguages(array $languages)
		{
			return ($best_language = fRequest::getBestAcceptType($types))
				? $best_language
				: self::triggerError('not_acceptable');
		}

		/**
		 * Determines whether or not accept the request method is allowed.  If
		 * the current request method is not in the list of allowed methods,
		 * the method will trigger the error 'not_allowed'
		 *
		 * @static
		 * @access protected
		 * @param array $methods An array of allowed request methods
		 * @return boolean TRUE if the current request method is in the array, FALSE otherwise
		 */
		static protected function allowMethods(array $methods = array())
		{
			$request_method  = strtoupper($_SERVER['REQUEST_METHOD']);
			$allowed_methods = array_map('strtoupper', $methods);

			if (!in_array($request_method, $allowed_methods)) {
				self::triggerError('not_allowed', NULL, NULL, array(
					'Allow: ' . implode(', ', $allowed_methods)
				));
				return FALSE;
			}

			return TRUE;
		}

		/**
		 * Redirect to a controller target.
		 *
		 * @static
		 * @access protected
		 * @param string $target an inKWell target to redirect to
		 * @param array $query an associative array containing parameters => values
		 * @return mixed
		 */
		static protected function redirect($target, $query = array())
		{
			fURL::redirect(iw::makeLink($target, $query));
		}

		/**
		 * Trigger an error if a function fails uniquely.
		 *
		 * @static
		 * @access protected
		 * @param mixed $value The value to check.  If this matches the current iw::$failureToken the provided error will be triggered
		 * @param string $error The name of the error to trigger upon failure, defaults to 'not_found'
		 * @return mixed The original value upon success
		 */
		static protected function demand($value, $error = 'not_found')
		{
			return (iw::checkFailureToken($value))
				? self::triggerError($error)
				: $value;
		}

		/**
		 * Attempts to execute a target within the context of of Controller.
		 * By default the execution of the target is optional, meaning the
		 * target need not exist.  You can wrap this function in ::demand()
		 * in order to require it.
		 *
		 * @static
		 * @access protected
		 * @param string $target An inKWell target to execute
		 * @param mixed Additional parameters to pass to the callback
		 * @return mixed The return of the callback, if valid, an inKWell failure token otherwise.
		 */
		static protected function exec($target)
		{
			if (is_callable($target)) {
				$params = array_slice(func_get_args(), 1);
				return call_user_func_array($target, $params);
			}

			return iw::makeFailureToken();
		}

		/**
		 * Attempts to delegate control to a file within the context of
		 * Controller.  By default the delegation is optional, meaning the
		 * file need not exist.  You can wrap this function in ::demand() in
		 * order to require it.
		 *
		 * @static
		 * @access protected
		 * @param string|fFile $file The file to delegate control to
		 * @return mixed The return of the included file, if accessible, an inKWell failure token otherwise
		 */
		static protected function delegate($file)
		{
			try {
				if (!($file instanceof fFile)) {
					$file = new fFile($file);
				}
				return include $file->getPath();
			} catch (fValidationException $e) {

			}

			return iw::makeFailureToken();
		}

		/**
		 * Determines the base URL from the server's request URI
		 *
		 * @static
		 * @access protected
		 * @param void
		 * @return string The Base URL
		 */
		static protected function getBaseURL()
		{
			if (self::$baseURL == NULL) {
				self::$baseURL  = self::DEFAULT_SITE_SECTION;
				$request_info   = parse_url(Moor::getRequestPath());
				$request_path   = ltrim($request_info['path'], '/');
				$request_parts  = explode('/', $request_path);
				$site_sections  = array_keys(self::$siteSections);

				// If the request meets these conditions it will overwrite the
				// base URL.

				$has_base_url   = (in_array($request_parts[0], $site_sections));
				$is_not_default = ($request_parts[0] != self::$baseURL);
				$is_sub_request = (count($request_parts) > 1);

				if ($has_base_url && $is_not_default && $is_sub_request) {
					self::$baseURL = array_shift($request_parts);
				}

				self::$requestPath = implode('/', $request_parts);
			}

			return self::$baseURL;
		}

		/**
		 * Determines the internal request path (i.e. without a baseURL)
		 *
		 * @static
		 * @access protected
		 * @param void
		 * @return string The internal request path
		 */
		static protected function getRequestPath()
		{
			if (self::$requestPath == NULL) {
				self::getBaseURL();
			}

			return self::$requestPath;
		}

		/**
		 * Determines the base path of the controller from the controller root
		 * and base URL.
		 *
		 * @static
		 * @access protected
		 * @param string $sub_directory An optional subdirectory to append
		 * @return fDirectory The full base path
		 */
		static protected function getBasePath($sub_directory = NULL)
		{
			if (self::$basePath == NULL) {
				self::$basePath = new fDirectory(
					self::$controllerRoot . self::getBaseURL()
				);
			}

			return new fDirectory(self::$basePath . $sub_directory);
		}

		/**
		 * Determines the request format for the resource.  The request format
		 * can be taken is as a get or URL parameter with the simple name
		 * 'request_format', but must be explicitly set on routes.
		 *
		 * If the request format is provided and the HTTP Accept header does
		 * not accept the appropriate mime-type a not_acceptable error will be
		 * triggered automatically.
		 *
		 * @static
		 * @access protected
		 * @param void
		 * @return string The request format, i.e. 'html', 'xml', 'json', etc...
		 */
		static protected function getRequestFormat()
		{
			if (self::$requestFormat === NULL) {
				$format = fRequest::get('request_format', 'string', NULL);

				if ($format) {
					self::$requestFormat = $format;
				} elseif (fRequest::isAjax()) {
					self::$requestFormat = self::$defaultAjaxRequestFormat;
				} else {
					self::$requestFormat = self::$defaultRequestFormat;
				}
			}

			return self::$requestFormat;
		}

		/**
		 * Gets the current directly accessed action
		 *
		 * @static
		 * @access protected
		 * @param void
		 * @return string The current directly accessed action
		 */
		static protected function getAction()
		{
			return Moor::getActiveShortMethod();
		}

		/**
		 * Gets the current directly accessed entry
		 *
		 * @static
		 * @access protected
		 * @param void
		 * @return string The current directly accessed entry
		 */
		static protected function getEntry()
		{
			return Moor::getActiveShortClass();
		}

		/**
		 * A quick way to check against the current base URL
		 *
		 * @static
		 * @access protected
		 * @param string $base_url The base URL to check against
		 * @return boolean TRUE if the base URL matched the current base URL, FALSE otherwise
		 */
		static protected function checkBaseURL($base_url)
		{
			return (self::getBaseURL() == $base_url);
		}

		/**
		 * A quick way to check against the current request format
		 *
		 * @static
		 * @access protected
		 * @param string $format The format to check for
		 * @return boolean TRUE if the format matches the current request format, FALSE otherwise
		 */
		static protected function checkRequestFormat($format)
		{
			return (strtolower($format) == self::getRequestFormat());
		}

		/**
		 * Determines whether or not a particular class is the entry class
		 * being used by the router.
		 *
		 * @static
		 * @access protected
		 * @param string $class The class to check against the router
		 * @return void
		 */
		static protected function checkEntry($class)
		{
			return (Moor::getActiveShortClass() == $class);
		}

		/**
		 * Determines whether or not a particular method is the action being
		 * used by the router.
		 *
		 * @static
		 * @access protected
		 * @param string $method The method name to check against the router
		 * @return void
		 */
		static protected function checkAction($method)
		{
			return (Moor::getActiveShortMethod() == $method);
		}

		/**
		 * Determines whether or not a particular class and method is the
		 * entry and action for the router.
		 *
		 * @static
		 * @access protected
		 * @param string $class The class to check against the router
		 * @param string $method The method name to check against the router
		 * @return void
		 */
		static protected function checkEntryAction($class, $method) {
			return (self::checkEntry($class) && self::checkAction($method));
		}

		/**
		 * Triggers a standard error which will attempt to use whatever error
		 * handlers have been assigned.  If the error is unknown an HTTP/1.0
		 * 500 Internal Server Error header will be sent.  Otherwise headers
		 * will be matched against any set error headers or the defaults.  If
		 * no handler is set a hard error will be triggered.
		 *
		 * @static
		 * @access protected
		 * @param string $error The error to be triggered.
		 * @param string $message_type The type of message to display
		 * @param string $message The message to be displayed
		 * @param array  $added_headers Additional headers to output after the initial header
		 * @return void
		 */
		static protected function triggerError($error, $message_type = NULL, $message = NULL, array $added_headers = array())
		{
			self::$requestFormat = FALSE;
			self::acceptTypes();

			$message_type = ($message_type)
				? $message_type
				: self::MSG_TYPE_ERROR;

			$error_info   = array(
				'handler' => NULL,
				'header'  => 'HTTP/1.0 500 Internal Server Error',
				'message' => 'An Unknown error occurred.'
			);

			if (isset(self::$errors[$error])) {
				$error_info = array_merge($error_info, self::$errors[$error]);
				$message    = ($message) ? $message : $error_info['message'];

				@header($error_info['header']);

				foreach ($added_headers as $header) {
					@header($header);
				}

				if ($handler = $error_info['handler']) {
					$message = fText::compose($message);

					fMessaging::create($message_type, $handler, $message);
					self::exec($handler);
					return;
				}
			}

			self::triggerHardError($error, $error_info['message']);
		}

		/**
		 * Triggers a hard error doing little more than outputting the message
		 * on the screen, this should not be called except by extended error
		 * handlers or by Controller::triggerError()
		 *
		 * @static
		 * @access protected
		 * @param string $error The error being sent.
		 * @param string $message The message to output with it.
		 * @return void The function exits the script.
		 */
		static protected function triggerHardError($error, $message)
		{
			$self    = new self();
			$title   = fText::compose(fGrammar::humanize($error));
			$message = fText::compose($message);

			$self->view
				-> pack   ('id',       $error)
				-> push   ('classes',  self::MSG_TYPE_ERROR)
				-> push   ('title',    $title)
				-> digest ('contents', $message);

			$self->view->render();
			exit();
		}

		/**
		 * Sets error information for the Controller
		 *
		 * @static
		 * @access protected
		 * @param string $error The error to set a handler for
		 * @param string $handler An inKWell target to execute if the error is triggered
		 * @param string $header The HTTP header to output if the error is triggered
		 * @param string $message A default message to display explaining the error
		 * @return void
		 */
		static private function setError($error, $handler = NULL, $header = NULL, $message = NULL)
		{
			self::$errors[$error]['handler'] = $handler;
			self::$errors[$error]['header']  = $header;
			self::$errors[$error]['message'] = $message;
		}
	}
