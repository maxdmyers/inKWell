<?php

	/**
	 * The KwikiController, a standard controller class.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 */
	class KwikiController extends Controller
	{

		const DEFAULT_STORAGE_PATH = 'kwiki/pages';
		const DEFAULT_TITLE        = 'Kwiki';

		static private $pagesDirectory = NULL;
		static private $pagePath = NULL;
		static private $title = NULL;
		static private $disqusId = NULL;
		static private $gaUaId = NULL;

		/**
		 * Initializes all static class information for the WikiController class
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return boolean TRUE if the initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array(), $element = NULL)
		{
			self::$pagesDirectory = iw::getWriteDirectory(
				isset($config['storage_path'])
					? $config['storage_path']
					: self::DEFAULT_STORAGE_PATH
			);

			self::$pagePath = trim(str_replace('/', '.', fURL::get()), '.');

			if (strpos(self::$pagePath, '/../') !== FALSE) {
				fURL::redirect(str_replace('/../', '/', self::$pagePath));
			}

			$markdown_dirs = array('includes/lib/markdown_extended');

			iw::loadClass('MarkdownExtra_Parser',         $markdown_dirs);
			iw::loadClass('MarkdownExtraExtended_Parser', $markdown_dirs);


			self::$title = isset($config['title'])
				? $config['title']
				: self::DEFAULT_TITLE;

			self::$disqusId = isset($config['disqus_id'])
				? $config['disqus_id']
				: NULL;

			self::$gaUaId = isset($config['ga_ua_id'])
				? $config['ga_ua_id']
				: NULL;

			return TRUE;
		}

		/**
		 * Shows the requested resource or a preview if provided Markdown source
		 *
		 * @static
		 * @access public
		 * @param string Source to preview instead of loading from the URI
		 * @return View
		 */
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

				return View::create('kwiki/default.php')
					-> digest ('content',  $source)
					-> set    ('comments', 'kwiki/comments.php')
					-> pack   ('title', self::$title)
					-> pack   ('disqus_id', self::$disqusId)
					-> pack   ('ga_ua_id', self::$gaUaId);

			} catch (fValidationException $e) {
				return self::notFound();
			}
		}

		/**
		 * Provides the edit functionality for displaying a file for edit or storing it
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return view
		 */
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

			return View::create('kwiki/default.php')
				-> set  ('content', 'kwiki/edit.php')
				-> pack ('title', self::$title)
				-> pack ('ga_ua_id', self::$gaUaId)
				-> pack ('source', $source);
		}

		/**
		 * The not found handler
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return View The not found view
		 */
		static public function notFound()
		{
			header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
			return View::create('kwiki/default.php')
				-> set  ('content', 'kwiki/not_found.php')
				-> pack ('title', self::$title . ' - Not Found')
				-> pack ('ga_ua_id', self::$gaUaId);

		}

		/**
		 * Loads or creates a file resource based on the pages directory and page path which is
		 * derived from the URL.
		 *
		 * @static
		 * @access private
		 * @param boolean Whether or not the file needs to be created
		 * @return fFile The file resource
		 */
		static private function loadURI($create = FALSE)
		{
			$file = self::$pagesDirectory . DIRECTORY_SEPARATOR . self::$pagePath;

			return ($create)
				? fFile::create($file, '')
				: new fFile($file);
		}
	}
