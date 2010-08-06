<?php

	/**
	 * A base controller class which provides facilities for triggering various
	 * responses, and building higher level controllers.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class Controller extends MoorAbstractController
	{

		const CONTROLLER_SUFFIX      = __CLASS__;

		const DEFAULT_VIEW_ROOT      = 'views';
		const DEFAULT_REQUEST_FORMAT = 'html';

		const MSG_TYPE_ERROR         = 'error';
		const MSG_TYPE_ALERT         = 'alert';
		const MSG_TYPE_SUCCESS       = 'success';

		// Instiated controllers have a localized view

		protected        $view                 = NULL;

		// Handlers for common errors

		static private   $errors               = array();

		// State information

		static private   $requestFormat        = NULL;
		static private   $typeHeadersSent      = FALSE;

		/**
		 * Builds a new controller by assigning it a local view and running
		 * prepare if it exists.
		 *
		 * @param void
		 * @return void
		 */
		final public function __construct()
		{
			$this->view = new View();

			if (method_exists($this, 'prepare')) {
				$prepare_callback  = array($this, 'prepare');
				call_user_func_array($prepare_callback, func_get_args());
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
			switch(self::getRequestFormat()) {

				case 'html':
					$this->view->load('html.php')
						-> add  ('styles',  '/user/styles/common.css')
						-> add  ('scripts', '/user/scripts/common.js');
					break;

				case 'json':
					break;

				case  'xml':
					break;
			}

			$this->view->onRender('Controller::__sendContentType');
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
			if (isset($config['errors'])) {
				if (!is_array($config['errors'])) {
					throw new fProgrammerException (
						'Error configuration requires an array.'
					);
				}
				foreach ($config['errors'] as $error => $info) {
					if (!is_array($info)) {
						throw new fProgrammerException (
							'Error %s must be configured as an array.'
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
		 * Sends the content type based on the request format
		 *
		 * @param void
		 * @return void
		 */
		static public function __sendContentType()
		{
			if (!self::$typeHeadersSent) {
				switch(self::getRequestFormat()) {
					case 'html':
						@fHTML::sendHeader();
						break;
					case 'json':
						@fJSON::sendHeader();
						break;
					case 'xml':
						@fXML::sendHeader();
						break;
				}
				self::$typeHeadersSent = TRUE;
			}
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
			$target = iw::loadTarget($target);
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
				-> pack   ('id',              $error)
				-> push   ('title',           fGrammar::humanize($error))
				-> digest ('primary_section', $message);

			$controller->view->render();
			exit();
		}

		/**
		 * Determines the request format for the resource
		 *
		 * @return string The request format, i.e. 'html' (default), 'xml', or 'json'
		 */
		static protected function getRequestFormat()
		{
			if (self::$requestFormat === NULL) {
				if ($format = fRequest::get('request_format', 'string', NULL)) {
					fSession::set('request_format', $format);
				} elseif (!fSession::get('request_format', NULL)) {
					fSession::set('request_format', self::DEFAULT_REQUEST_FORMAT);
				}

				self::$requestFormat = fSession::get('request_format');

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
			return (self::getRequestFormat() == $format);
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
