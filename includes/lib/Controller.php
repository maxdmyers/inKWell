<?php

	/**
	 * A base controller class which provides facilities for triggering various
	 * responses, and building higher level controllers.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class Controller extends MoorAbstractController
	{

		const CONTROLLER_SUFFIX      = 'Controller';

		const DEFAULT_VIEW_ROOT      = 'views';
		const DEFAULT_REQUEST_FORMAT = 'html';

		const DEFAULT_SITE_SECTION   = 'default';
		const DEFAULT_SITE_TITLE     = 'inKWell Site';

		const NOT_AUTHORIZED_MSG     = 'You must be logged in to view the requested resource.';
		const NOT_FOUND_MSG          = 'The requested resource could not be found.';
		const FORBIDDEN_MSG          = 'You do not have permission to view the requested resource.';

		const MSG_TYPE_ERROR         = 'error';
		const MSG_TYPE_ALERT         = 'alert';
		const MSG_TYPE_SUCCESS       = 'success';

		// Instiated controllers have a localized view

		protected        $view                 = NULL;

		// Handlers for common errors

		static private   $notFoundHandler      = NULL;
		static private   $notAuthorizedHandler = NULL;
		static private   $forbiddenHandler     = NULL;

		// State information

		static private   $requestFormat        = NULL;
		static private   $headersSent          = FALSE;

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
			switch(self::getRequestFormat(TRUE)) {

				case 'html':
					$this->view->load('html.php')
						-> add  ('styles',       '/support/styles/main.css')
						-> add  ('scripts',      '/support/scripts/common.js');
					break;

				case 'json':
					break;

				case  'xml':
					break;
			}
		}

		/**
		 * Initializes the global controller
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
			if (isset($config['not_found_handler'])) {
				self::setNotFoundHandler($config['not_found_handler']);
			}

			if (isset($config['not_authorized_handler'])) {
				self::setNotAuthorizedHandler($config['not_authorized_handler']);
			}

			if (isset($config['forbidden_handler'])) {
				self::setForbiddenHandler($config['forbidden_handler']);
			}
		}

		/**
		 * Sets the not found handler for the controller.  This can only
		 * be set by controllers, not publically.
		 *
		 * @param callback|string $handler A valid callback or string representing a redirect URL
		 * @return void
		 */
		static protected function setNotFoundHandler($handler)
		{
			self::$notFoundHandler = $handler;
		}

		/**
		 * Sets the not authorized handler for the controller.  This can only
		 * be set by controllers, not publically.
		 *
		 * @param callback|string $handler A valid callback or string representing a redirect URL
		 * @return void
		 */
		static protected function setNotAuthorizedHandler($handler)
		{
			self::$notAuthorizedHandler = $handler;
		}

		/**
		 * Sets the forbidden handler for the controller.  This can only be set
		 * by controllers, not publically.
		 *
		 * @param callback|string $handler A valid callback or string representing a redirect URL
		 * @return void
		 */
		static protected function setForbiddenHandler($handler)
		{
			self::$forbiddenHandler = $handler;
		}

		/**
		 *  Trigger a not found response and appropriate output.
		 *
		 * @param boolean $force_death Whether or not to abandon handler attempts
		 * @param string $message_type A string representation of the error type... 'error', 'alert', etc.
		 * @param string $message A more specific message to generate
		 * @return void
		 */
		static protected function triggerNotFound($force_death = FALSE, $message_type = NULL, $message = NULL)
		{
			@header("HTTP/1.0 404 Not Found");

			if ($message_type == NULL) {
				$message_type = self::MSG_TYPE_ERROR;
			}

			if ($message === NULL) {
				$message = self::NOT_FOUND_MSG;
			}

			if (!$force_death && self::$notFoundHandler !== NULL) {

				fMessaging::create($message_type, self::$notFoundHandler, $message);

				if (is_callable(self::$notFoundHandler)) {
					call_user_func(self::$notFoundHandler);
				} else {
					fURL::redirect(self::$notFoundHandler);
				}

				return;
			}

			$error = new Controller();

			$error->view
				-> pack   ('id',              'not_found')
				-> push   ('title',           'Not Found')
				-> digest ('primary_section', $message);

			$error->view->render();
		}

		/**
		 *  Trigger a not authorized response and appropriate output.
		 *
 		 * @param boolean $force_death Whether or not to abandon handler attempts
		 * @param string $message_type A string representation of the error type... 'error', 'alert', etc.
		 * @param string $message A more specific message to generate
		 * @return void
		 */
		static protected function triggerNotAuthorized($force_death = FALSE, $message_type = NULL, $message = NULL)
		{
			@header('HTTP/1.0 401 Unauthorized');

			if ($message_type === NULL) {
				$message = self::MSG_TYPE_ALERT;
			}

			if ($message === NULL) {
				$message = self::NOT_AUTHORIZED_MSG;
			}

			if (!$force_death && self::$notAuthorizedHandler !== NULL) {

				fMessaging::create($message_type, self::$notAuthorizedHandler, $message);

				if (is_callable(self::$notAuthorizedHandler)) {
					call_user_func(self::$notAuthorizedHandler);
				} else {
					fURL::redirect(self::$notAuthorizedHandler);
				}

				return;
			}

			$error = new Controller();

			$error->view
				-> pack   ('id',              'not_authorized')
				-> push   ('title',           'Not Authorized')
				-> digest ('primary_section', $message);

			$error->view->render();
		}

		/**
		 *  Trigger a forbidden response and appropriate output.
		 *
 		 * @param boolean $force_death Whether or not to abandon handler attempts
		 * @param string $message_type A string representation of the error type... 'error', 'alert', etc.
		 * @param string $message A more specific message to generate
		 * @return void
		 */
		static protected function triggerForbidden($force_death = FALSE, $message_type = NULL, $message = NULL)
		{
			@header("HTTP/1.0 403 Forbidden");

			if ($message_type === NULL) {
				$message = self::MSG_TYPE_ERROR;
			}

			if ($message === NULL) {
				$message = self::FORBIDDEN_MSG;
			}

			if (!$force_death && self::$forbiddenHandler !== NULL) {

				fMessaging::create($message_type, self::$forbiddenHandler, $message);

				if (is_callable(self::$forbiddenHandler)) {
					call_user_func(self::$forbiddenHandler);
				} else {
					fURL::redirect(self::$forbiddenHandler);
				}

				return;
			}

			$error = new Controller();

			$error->view
				-> pack   ('id',             'forbidden')
				-> push   ('title',          'Forbidden')
				-> digest ('primary_section', $message);

			$error->view->render();
		}

		/**
		 * Determines the request format for the resource
		 *
		 * @param boolean $send_headers An optional parameter to signal whether or not headers should be sent
		 * @return string The request format, i.e. 'html' (default), 'xml', or 'json'
		 */
		static protected function getRequestFormat($send_headers = FALSE)
		{
			if (self::$requestFormat === NULL) {
				if ($format = fRequest::get('request_format', 'string', NULL)) {
					fSession::set('request_format', $format);
				} elseif (!fSession::get('request_format', NULL)) {
					fSession::set('request_format', self::DEFAULT_REQUEST_FORMAT);
				}

				self::$requestFormat = fSession::get('request_format');

			}

			if ($send_headers && !self::$headersSent) {
				switch(self::$requestFormat) {
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
				self::$headersSent = TRUE;
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
		 * Determines whether or not a particular class and optionally the
		 * method were the entry point for the request.
		 *
		 * @param string $class The class to check against for entry
		 * @param string $method An optional method name to check against for entry
		 * @return void
		 */
		static protected function isEntryPoint($class, $method = NULL)
		{
			if (!$method) {
				$method = Moor::getActiveShortMethod();
			}

			return (
				(Moor::getActiveShortClass()  == $class) &&
				(Moor::getActiveShortMethod() == $method)
			);
		}

		/**
		 * Attempts to delegate control to a file.  If the $required parameter
		 * set to TRUE then this function will execute triggerNotFound() if the
		 * file is not accessible.
		 *
		 * @param string|fFile $file The file to delegate control to
		 * @param boolean $required TRUE if the file is required, FALSE otherwise
		 */
		static protected function delegate($file, $required = FALSE)
		{
			try {
				$file = new fFile($file);
			} catch (fValidationException $e) {
				if ($required) {
					self::triggerNotFound();
					return;
				}
			}

			if ($file instanceof fFile) {
				include $file->getPath();
			}
		}

	}
