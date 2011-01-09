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

		const DEFAULT_VIEW_ROOT           = 'views';
		const DEFAULT_REQUEST_FORMAT      = 'html';
		const DEFAULT_AJAX_REQUEST_FORMAT = 'json';

		const MSG_TYPE_ERROR              = 'error';
		const MSG_TYPE_ALERT              = 'alert';
		const MSG_TYPE_SUCCESS            = 'success';

		// Instiated controllers have a localized view

		protected        $view                     = NULL;

		// Handlers for common errors

		static private   $errors                   = array();

		// Request Format Configuration

		static private   $defaultRequestFormat     = NULL;
		static private   $defaultAjaxRequestFormat = NULL;

		// State information

		static private   $requestFormat            = NULL;
		static private   $typeHeadersRegistered    = FALSE;

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
			$format = self::getRequestFormat();

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

			$this->view->load($format . '.php');
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
		 * Redirect to a controller target
		 *
		 * @param string $target an inKWell target to redirect to
		 * @param array $mapping an associative array containing routing keys to values
		 * @return void
		 */
		static protected function redirect($target, $mapping = array())
		{
			$params = array_keys($mapping);
			$target = (array_unshift($params, $target) == 1)
				? $target
				: implode(' ', $params);

			fURL::redirect(call_user_func_array(
				'Moor::linkTo',
				array_merge(array($target), $mapping)
			));
		}

		/**
		 * Attempts to execute a callback within the context of of Controller.
		 *
		 * @param string $target An inKWell target to execute
		 * @param mixed Additional parameters to pass to the callback
		 * @return mixed The return of the callback, if valid, NULL otherwise
		 */
		static protected function exec($target)
		{
			if (is_callable($target)) {
				$params = array_slice(func_get_args(), 1);
				return call_user_func_array($target, $params);
			} else {
				fURL::redirect($target);
			}
		}

		/**
		 * Attempts to delegate control to a file within the context of
		 * Controller.  If the $required parameter is set to TRUE then this
		 * function will execute triggerNotFound() if the file is not
		 * accessible.
		 *
		 * @param string|fFile $file The file to delegate control to
		 * @param boolean $required TRUE if the file is required, FALSE otherwise
		 * @return mixed The return of the included file, if accessible, NULL otherwise
		 */
		static protected function delegate($file, $required = FALSE)
		{
			try {
				$file = new fFile($file);
			} catch (fValidationException $e) {
				if ($required) {
					self::triggerError('not_found');
					return;
				}
			}

			if ($file instanceof fFile) {
				return include $file->getPath();
			}

			return NULL;
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
		static protected function setError($error, $handler = NULL, $header = NULL, $message = NULL)
		{
			self::$errors[$error]['handler'] = $handler;
			self::$errors[$error]['header']  = $header;
			self::$errors[$error]['message'] = $message;
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
		 * @return void
		 */
		static protected function triggerError($error, $message_type = NULL, $message = NULL)
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

				if ($handler = $error_info['handler']) {
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

			$controller->view
				-> pack   ('id',       $error)
				-> push   ('classes',  self::MSG_TYPE_ERROR)
				-> push   ('title',    fGrammar::humanize($error))
				-> digest ('contents', $message);

			$controller->view->render();
			exit();
		}

		/**
		 * Determines whether or not the request was made via AJAX
		 *
		 * @param void
		 * @return boolean TRUE if the request was made via AJAX, FALSE otherwise
		 */
		static protected function isRequestAjax()
		{
			return (
				isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
				($_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest')
			);
		}

		/**
		 * Determines the request format for the resource
		 *
		 * @return string The request format, i.e. 'html' (default), 'xml', or 'json'
		 */
		static protected function getRequestFormat()
		{
			if (self::$requestFormat === NULL) {

				if (!self::isRequestAjax()) {
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
		 * Determines whether or not a particular class is the entry class
		 * being used by the router.
		 *
		 * @param string $class The class to check against the router
		 * @return void
		 */
		static protected function isEntry($class)
		{
			return (Moor::getActiveShortClass()  == $class);
		}

		/**
		 * Determines whether or not a particular method is the action being
		 * used by the router.
		 *
		 * @param string $method The method name to check against the router
		 * @return void
		 */
		static protected function isAction($method)
		{
			return (Moor::getActiveShortMethod()  == $method);
		}

		/**
		 * Determines whether or not a particular class and method is the
		 * entry and action for the router.
		 *
		 * @param string $class The class to check against the router
		 * @param string $method The method name to check against the router
		 * @return void
		 */
		static protected function isEntryAction($class, $method) {
			return (self::isEntry($class) && self::isAction($method));
		}

	}
