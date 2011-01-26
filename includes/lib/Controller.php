<?php

	/**
	 * A base controller class which provides facilities for triggering various
	 * responses, and building higher level controllers.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
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

		// Instiated controllers have a localized view

		protected      $view                     = NULL;


		static private $controllerRoot           = NULL;
		static private $basePath                 = NULL;

		// Handlers for common errors

		static private $errors                   = array();

		// Request Format Configuration

		static private $defaultRequestFormat     = NULL;
		static private $defaultAjaxRequestFormat = NULL;

		// Site sections

		static private $siteSections             = array();
		static private $baseURL                  = NULL;
		static private $requestPath              = NULL;

		// State information

		static private $requestFormat            = NULL;
		static private $typeHeadersRegistered    = FALSE;

		/**
		 * Builds a new controller by assigning it a local view and running
		 * prepare if it exists.
		 *
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
		 * Prepares a new controller
		 *
		 * @param void
		 * @return void
		 */
		protected function prepare()
		{
			$format  = self::getRequestFormat();
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

			if (!self::$typeHeadersRegistered) {

				switch($format) {
					case 'html':
						$content_type_callback = 'fHTML::sendHeader';
						break;
					case 'json':
						$content_type_callback = 'fJSON::sendHeader';
						break;
					case 'xml':
						$content_type_callback = 'fXML::sendHeader';
						break;
				}

				$this->view->onRender($content_type_callback);

				self::$typeHeadersRegistered = TRUE;
			}

			$this->view
				-> load ($format . '.php')
				-> push ('title',   $title);
		}


		/**
		 * Matches whether or not a given class name is a potential
		 * Controller
		 *
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
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{

			self::$controllerRoot = implode(DIRECTORY_SEPARATOR, array(
				$_SERVER['DOCUMENT_ROOT'],
				trim(
					isset($config['controller_root'])
					? $config['controller_root']
					: self::DEFAULT_CONTROLLER_ROOT
					, '/\\'
				)
			));

			try {
				self::$controllerRoot = new fDirectory(self::$controllerRoot);
			} catch (fValidationException $e) {
				throw new fProgrammerException (
					'View root directory %s is not readable',
					self::$controllerRoot
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

			$controller_configs = array_unshift(
				iw::getConfigsByType('controller'),
				iw::getConfig('controller')
			);

			foreach ($controller_configs as $controller_config) {

				if (isset($controller_config['sections'])) {
					if (!is_array($controller_config['sections'])) {
						throw new fProgrammerException (
							'Site sections must be an array of base urls (keys) to titles (values)'
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
		 * Determines whether or not we should accept the request based on
		 * the mime type accepted by the user agent.
		 *
		 * @param array $types An array of acceptable mime types
		 * @return mixed The method will trigger a 'not_acceptable' error on failure, will return the best type upon success.
		 */
		static protected function acceptTypes(array $types = array())
		{
			return ($best_type = fRequest::getBestAcceptType($types))
				? $best_type
				: self::triggerError('not_acceptable');
		}

		/**
		 * Determines whether or not we should accept the request based on
		 * the languages accepted by the user agent.
		 *
		 * @param array $language An array of acceptable languages
		 * @return mixed The method will trigger a 'not_accepted' error on failure, will return the best type upon success.
		 */
		static protected function acceptLanguages(array $languages = array())
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
		 * @param string $target an inKWell target to redirect to
		 * @param array $query an associative array containing parameters => values
		 * @return mixed
		 */
		static protected function redirect($target, $query = array())
		{
			fURL::redirect(self::makeLink($target, $query));
		}

		/**
		 * Trigger an error if a function fails uniquely.
		 *
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
		 * Get a link to to a controller target
		 *
		 * @param string $target an inKWell target to redirect to
		 * @param array $query an associative array containing parameters => values
		 * @return void
		 */
		static protected function makeLink($target, $query = array())
		{
			if (!is_callable($target)) {

				$query = (count($query))
					? '?' . http_build_query($query)
					: NULL;

				if (strpos($target, '/') === 0 && Moor::getActiveProxyURI()) {
					return Moor::getActiveProxyURI() . $target . $query;
				}

				return $target . $query;
			}

			$params = array_keys($query);

			$target = (array_unshift($params, $target) == 1)
				? $target
				: implode(' ', $params);

			return call_user_func_array(
				'Moor::linkTo',
				array_merge(array($target), $query)
			);
		}

		/**
		 * Determines the base URL from the server's request URI
		 *
		 * @param void
		 * @return string The Base URL
		 */
		static protected function getBaseURL()
		{
			if (self::$baseURL == NULL) {
				self::$baseURL   = self::DEFAULT_SITE_SECTION;
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
		 * Determines the base path of the controller from the controller root
		 * and base URL.
		 *
		 * @param string $sub_directory An optional subdirectory to append
		 * @return string The full base path
		 */
		static protected function getBasePath($sub_directory = NULL)
		{

			if (self::$basePath == NULL || $sub_directory !== NULL) {
				self::$basePath = implode(DIRECTORY_SEPARATOR, array(
					self::$controllerRoot . self::getBaseURL(),
					$sub_directory
				));
			}

			return self::$basePath;
		}

		/**
		 * Determines the internal request path (i.e. without a baseURL)
		 *
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
		 * Determines the request format for the resource
		 *
		 * @return string The request format, i.e. 'html', 'xml', 'json', etc...
		 */
		static protected function getRequestFormat()
		{
			if (self::$requestFormat === NULL) {

				if (!fRequest::isAjax()) {
					$request_format_key     = 'request_format';
					$default_request_format = self::$defaultRequestFormat;
				} else {
					$request_format_key     = 'ajax_request_format';
					$default_request_format = self::$defaultAjaxRequestFormat;
				}

				if ($format = fRequest::get('request_format', 'string', NULL)) {
					fSession::set($request_format_key, strtolower($format));
				} elseif (!fSession::get($request_format_key, NULL)) {
					fSession::set($request_format_key, $default_request_format);
				}

				self::$requestFormat = fSession::get($request_format_key);
			}

			return self::$requestFormat;
		}

		/**
		 * Gets the current directly accessed action
		 *
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
		 *sd
		 * @param string $error The error to be triggered.
		 * @param string $message_type The type of message to display
		 * @param string $message The message to be displayed
		 * @param array  $added_headers Additional headers to output after the initial header
		 * @return void
		 */
		static protected function triggerError($error, $message_type = NULL, $message = NULL, array $added_headers = array())
		{
			$message_type  = ($message_type) ? $message_type : self::MSG_TYPE_ERROR;

			$error_info    = array(
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
		 * @param string $error The error being sent.
		 * @param string $message The message to output with it.
		 * @return void The function exits the script.
		 */
		static protected function triggerHardError($error, $message)
		{
			$controller = new Controller();
			$title      = fText::compose(fGrammar::humanize($error));
			$message    = fText::compose($message);

			$controller->view
				-> pack   ('id',       $error)
				-> push   ('classes',  self::MSG_TYPE_ERROR)
				-> push   ('title',    $title)
				-> digest ('contents', $message);

			$controller->view->render();
			exit();
		}

		/**
		 * Sets error information for the Controller
		 *
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
