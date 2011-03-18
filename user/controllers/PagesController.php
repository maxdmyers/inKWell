<?php
	/**
	 * The PagesController is used in two ways, firstly any other controller
	 * use it to build a page and embed it's view within, secondly it is able
	 * to load separate files as controllers. Think of it as a generic
	 * wrapper for other controller views. Additionally:
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell::Extensions::PagesController
	 */
	class PagesController extends Controller
	{

		const DEFAULT_PAGES_ROOT = 'pages';

		/**
		 * The page path as determined by the configured pages root and
		 * the site section.
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $pagePath = NULL;

		/**
		 * Prepares the page
		 *
		 * @access protected
		 * @param void
		 * @return void
		 */
		protected function prepare()
		{

			$section = self::getBaseURL();
			$scripts = '/user/scripts/';
			$styles  = '/user/styles/';

			parent::prepare();

			switch (self::getRequestFormat()) {

				case 'html':
					$this->view
						-> add  ('styles',  $styles  . 'common.css')
						-> add  ('scripts', $scripts . 'common.js')
						-> add  ('styles',  $styles  . $section . '/common.css')
						-> add  ('scripts', $scripts . $section . '/common.js')
						-> add  ('header',  $section . '/pages/header.php')
						-> add  ('footer',  $section . '/pages/footer.php')
						-> push ('classes', $section);
					break;
			}

		}

		/**
		 * Initializes the PagesController
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
			// Connect the pages root

			self::$pagePath = self::getBasePath(isset($config['pages_root'])
				? $config['pages_root']
				: self::DEFAULT_PAGES_ROOT
			);

			return TRUE;
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
			$file = implode(DIRECTORY_SEPARATOR, array(
				self::$pagePath,
				'not_found.php'
			));

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
			$file = implode(DIRECTORY_SEPARATOR, array(
				self::$pagePath,
				'not_authorized.php'
			));

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
			$file = implode(DIRECTORY_SEPARATOR, array(
				self::$pagePath,
				'forbidden.php'
			));

			if (is_readable($file)) {
				self::delegate($file);
			} else {
				$target  = iw::makeTarget(__CLASS__, __FUNCTION__);
				$message = fMessaging::retrieve(self::MSG_TYPE_ERROR, $target);
				self::triggerHardError('forbidden', $message);
			}
		}

	}

