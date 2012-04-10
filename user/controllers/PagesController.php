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
		static private $pagePath       = NULL;

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
			self::$pagePath       = trim(str_replace('/', '_', fURL::get()), '_');

			if (strpos(self::$pagePath, '/../') !== FALSE) {
				fURL::redirect(str_replace('/../', '/', self::$pagePath));
			} 

			return TRUE;
		}


		static public function show($source = NULL)
		{
			if (!$source && (fRequest::check('create') || fRequest::check('edit'))) {
				return self::edit();
			}

			try {
				$parser = new MarkdownExtraExtended_Parser();

				if ($source) {
					fSession::set('source', $source);
					$source = $parser->transform($source);
				} else {
					$source = $parser->transform(self::loadURI()->read());
				}

				return View::create('default.php')->digest('content', $source);

			} catch (fValidationException $e) {
				return self::triggerError('not_found');
			}
		}

		static public function edit()
		{
			$source = fRequest::get('source', 'string', NULL);

			if (fRequest::isPost()) {
				switch(fRequest::get('action', 'string', 'save')) {
					case 'preview':
						return self::show($source);
					case 'save':
						try {
							$file = self::loadURI();
						} catch (fValidationException $e) {
							$file = self::loadURI(TRUE);
						}

						try {
							$file->write($source);
						} catch (fException $e) {}

						fURL::redirect();
				}
			}

			if (!$source) {
				try {			
					$source = self::loadURI()->read();
				} catch (fValidationException $e) {}
			}

			return View::create('default.php')->set('content', 'edit.php')->pack('source', $source); 
		}

		static public function notFound()
		{
			return View::create('default.php')->set('content', 'not_found.php');
		}

		static private function loadURI($create = FALSE)
		{
			$file = self::$pagesDirectory . DIRECTORY_SEPARATOR . self::$pagePath;

			return ($create)
				? fFile::create($file, '')
				: new fFile($file);
		}

	}
