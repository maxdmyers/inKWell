<?php

	/**
	 * The PagesController is used in two ways, firstly any other controller
	 * use it to build a page and embed it's view within, secondly it is able
	 * to load separate files as controllers. Think of it as a generic
	 * wrapper for other controller views. Additionally:
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class PagesController extends Controller
	{

		const DEFAULT_PAGES_ROOT     = 'pages';

		const DEFAULT_SITE_SECTION   = 'default';
		const DEFAULT_SITE_TITLE     = 'inKWell Site';

		static private   $siteSections = array();
		static private   $baseURL      = NULL;
		static private   $requestPath  = NULL;
		static private   $pagePath     = NULL;

		/**
		 * Prepares the page
		 *
		 * @param string $view The view to render if you so choose to support multiple views per format
		 * @return void
		 */
		protected function prepare()
		{
			parent::prepare();

			$section = self::getBaseURL();

			switch (self::getRequestFormat()) {

				case 'html':
					$this->view
						-> add  ('styles',  '/user/styles/'  . $section . '/common.css')
						-> add  ('scripts', '/user/scripts/' . $section . '/common.js')
						-> add  ('header',  'pages/' . $section . '/header.php')
						-> add  ('footer',  'pages/' . $section . '/footer.php')
						-> push ('title',   self::$siteSections[$section])
						-> push ('classes', $section);
					break;

				case 'json':
					break;

				case  'xml':
					break;
			}

		}

		/**
		 * Initializes the PagesController
		 *
		 * @param void
		 * @return void
		 */
		static public function __init($config)
		{

			// Build our site sections

			if (isset($config['sections'])) {
				if (!is_array($config['sections'])) {
					throw new fProgrammerException (
						'Site sections must be an array of base urls (keys) to titles (values)'
					);
				} elseif (!count($config['sections'])) {
					$site_sections = array(
						self::DEFAULT_SITE_SECTION => self::DEFAULT_SITE_TITLE
					);
				}
			} else {
				$site_sections = $config['sections'];
			}

			self::$siteSections = $config['sections'];

			// Connect the pages root

			$pages_root = implode(DIRECTORY_SEPARATOR, array(
				$_SERVER['DOCUMENT_ROOT'],
				trim(
					isset($config['pages_root'])
					? $config['pages_root']
					: self::DEFAULT_PAGES_ROOT
					, '/\\'
				)
			));

			self::$pagePath = implode(DIRECTORY_SEPARATOR, array(
				$pages_root,
				self::getBaseURL()
			));
		}

		/**
		 * Determines the baseURL from the server's request URI
		 *
		 * @param void
		 * @return string The Base URL
		 */
		static protected function getBaseURL()
		{
			if (self::$baseURL == NULL) {
				self::$baseURL   = self::DEFAULT_SITE_SECTION;
				$request_info    = parse_url($_SERVER['REQUEST_URI']);
				$request_path    = ltrim($request_info['path'], '/');
				$request_parts   = explode('/', $request_path);
				$site_sections   = array_keys(self::$siteSections);

				// If the request meets these conditions it will overwrite the
				// base URL.

				$has_base_url    = (in_array($request_parts[0], $site_sections));
				$is_not_default  = ($request_parts[0] != self::$baseURL);
				$has_sub_request = (count($request_parts) > 1);

				if ($has_base_url && $is_not_default && $has_sub_request) {
					self::$baseURL = array_shift($request_parts);
				}

				self::$requestPath = implode('/', $request_parts);
			}

			return self::$baseURL;
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
		 * Attempts to load index.php from a path comprised of the
		 * PageController's pages path and the original request URI.
		 *
		 * @param void
		 * @return void
		 */
		static public function load()
		{
			$request_parts = explode('/', self::getRequestPath());
			$last_part     = array_pop($request_parts);

			if (!$last_part) {
				$request_parts[] = 'index.php';
			} else {
				$request_parts[] = $last_part . '.php';
			}

			array_unshift($request_parts, self::$pagePath);

			$file = implode(DIRECTORY_SEPARATOR, $request_parts);

			self::delegate($file, TRUE);
		}

		/**
		 * Attempts to load the not_found.php file in the PageController's
		 * pages path or triggers an exit route through the parent controller
		 * if that cannot be found.
		 *
		 * @param void
		 * @return void
		 */
		static protected function notFound()
		{
			$file = self::$pagePath . DIRECTORY_SEPARATOR . 'not_found.php';

			if (is_readable($file)) {
				self::delegate($file);
			} else {
				$target  = iw::makeTarget(__CLASS__, __FUNCTION__);
				$message = fMessaging::retrieve(self::MSG_TYPE_ERROR, $target);
				self::triggerHardError('not_found', $message);
			}
		}

		/**
		 * Attempts to load the not_authorized.php file in the PageController's
		 * pages path or triggers an exit route through the parent controller
		 * if that cannot be found.
		 *
		 * @param void
		 * @return void
		 */
		static protected function notAuthorized()
		{
			$file = self::$pagePath . DIRECTORY_SEPARATOR . 'not_authorized.php';

			if (is_readable($file)) {
				self::delegate($file);
			} else {
				$target  = iw::makeTarget(__CLASS__, __FUNCTION__);
				$message = fMessaging::retrieve(self::MSG_TYPE_ERROR, $target);
				self::triggerHardError('not_authorized', $message);
			}
		}

		/**
		 * Attempts to load the forbidden.php file in the PageController's
		 * pages path or triggers an exit route through the parent controller
		 * if that cannot be found.
		 *
		 * @param void
		 * @return void
		 */
		static protected function forbidden()
		{
			$file = self::$pagePath . DIRECTORY_SEPARATOR . 'forbidden.php';

			if (is_readable($file)) {
				self::delegate($file);
			} else {
				$target  = iw::makeTarget(__CLASS__, __FUNCTION__);
				$message = fMessaging::retrieve(self::MSG_TYPE_ERROR, $target);
				self::triggerHardError('forbidden', $message);
			}
		}

	}
