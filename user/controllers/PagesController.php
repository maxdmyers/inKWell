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

		static private   $pagePath   = NULL;

		/**
		 * Prepares the page
		 *
		 * @param string $view The view to render if you so choose to support multiple views per format
		 * @return void
		 */
		protected function prepare()
		{

			$section = self::getBaseURL();

			parent::prepare();

			switch (self::getRequestFormat()) {

				case 'html':
					$this->view
						-> add  ('styles',  '/user/styles/common.css')
						-> add  ('scripts', '/user/scripts/common.js')
						-> add  ('styles',  '/user/styles/'  . $section . '/common.css')
						-> add  ('scripts', '/user/scripts/' . $section . '/common.js')
						-> add  ('header',  $section . '/pages/header.php')
						-> add  ('footer',  $section . '/pages/footer.php')
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
			// Connect the pages root

			self::$pagePath = self::getBasePath(isset($config['pages_root'])
				? $config['pages_root']
				: self::DEFAULT_PAGES_ROOT
			);
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

			self::demand(self::delegate($file));
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

