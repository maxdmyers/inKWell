<?php

	/**
	 * The PagesController, a standard controller class.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 */
	class PagesController extends Controller
	{

		static private $pagesDirectory = NULL;
		static private $pagePath = NULL;

		/**
		 * Prepares a new PagesController for running actions.
		 *
		 * @access protected
		 * @param string void
		 * @return void
		 */
		protected function prepare()
		{
			// The controller prepare method should be called only if you
			// are building out full pages or responses, not for controllers
			// which only provide embeddable views.
			//
			// return parent::prepare(__CLASS__);
		}

		/**
		 * Initializes all static class information for the PagesController class
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return boolean TRUE if the initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array(), $element = NULL)
		{
			// All custom initialization goes here, make sure to check any
			// configuration you're setting up for errors and return FALSE
			// in the event the class cannot be initialized with the provided
			// configuration.

			self::$pagesDirectory = iw::getWriteDirectory('pages');
			self::$pagePath       = $_SERVER['REQUEST_URI'];

			iw::loadClass('MarkdownExtraExtended_Parser');

			if (strpos(self::$pagePath, '/../') !== FALSE) {
				fURL::redirect(str_replace('/../', '/', self::$pagePath));
			} 

			return TRUE;
		}


		static public function show()
		{
			try {
				$page = new fFile(self::$pagesDirectory . DIRECTORY_SEPARATOR . self::$pagePath);

				return View::create('default.php')->digest('content', MarkdownExtended($page->read()));

			} catch (fValidationException $e) {
				self::triggerError('not_found');
			}
		}

		static public function edit()
		{

		}

		static public function notFound()
		{
			return View::create('default.php', array(
				'content' => View::create('not_found.php')
			));
		}

	}
