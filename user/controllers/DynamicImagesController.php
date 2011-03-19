<?php

	/**
	 * The DynamicImagesController adds support scaling database referenced
	 * images dynamically through different URLs.  This avoids the need to
	 * use CSS scaling which may detriment quality, and also to ensure easy
	 * mimetype conversion.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell::Extensions::DynamicImages
	 */
	class DynamicImagesController extends Controller
	{
		const DEFAULT_CACHE_DIRECTORY = 'cache/images';

		private $cached = FALSE;

		/**
		 * The root cache directory
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $cacheDirectory = NULL;

		/**
		 * An array of valid request formats
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $validFormats = NULL;

		/**
		 * Prepares a new DynamicImagesController for running actions.
		 *
		 * @access protected
		 * @param string $entry
		 * @param string $slug
		 * @param string $column
		 * @param string $cache_dir
		 * @return void
		 */
		protected function prepare($entry, $slug, $column, $format)
		{
			if (!$entry || !$slug || !$column || !$format) {
				throw new fProgrammerException(
					'You must provide at least an entry, slug, and column.'
				);
			}

			if (!in_array($format, self::$validFormats)) {
				throw new fProgrammerException(
					'The requested format is not a valid output format.'
				);
			}

			$record_class = ActiveRecord::classFromEntry($entry);
			$record       = ActiveRecord::createFromSlug($slug);
			$cache_path   = implode(DIRECTORY_SEPARATOR, array(
				self::$cacheDirectory,
				$entry,
				fGrammar::pluralize($column),
				iw::makeTarget($slug, md5(fURL::get()))
			));

			try {
				$this->view   = new fImage($cache_path);
				$this->cached = TRUE;
			} catch (fException $e) {
				$method     = 'get' . fGrammar::camelize($column, TRUE);

				//TODO: should be loading up and copying the image to the cache
				//TODO: path, and returning that one.

				$this->view = new fImage($record->$method());
			}
		}

		/**
		 * Initializes all static class information for the
		 * DynamicImagesController class.
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @return boolean TRUE if initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array())
		{
			self::$cacheDirectory = (isset($config['cache_directory']))
				? iw::getWriteDirectory($config['cache_directory'])
				: iw::getWriteDirectory(self::DEFAULT_CACHE_DIRECTORY);

			self::$validFormats   = (isset($config['valid_formats']))
				? $config['valid_formats']
				: array('jpg', 'png');

			return TRUE;
		}

		/**
		 * Scale an image using specific dimensions.
		 *
		 * @static
		 * @access public
		 * @param string $entry The entry name for the record, ex. users
		 * @param string $slug The string representation of the pkey, e.g. 1
		 * @param string $column The name of the column, ex. portrait
		 * @param integer $width The width to scale to
		 * @param integer $height The height to scale to
		 * @return fImage The fImage object representing the image
		 */
		static public function scale($entry = NULL, $slug = NULL, $column = NULL, $width = NULL, $height = NULL, $format = NULL)
		{
			$entry  = fRequest::get('entry', 'string', $entry);
			$slug   = fRequest::get('slug', 'string', $slug);
			$column = fRequest::get('column', 'string', $column);
			$width  = fRequest::get('width', 'string', $width);
			$height = fRequest::get('height', 'string', $height);
			$format = ($format)
				? $format
				: self::getRequestFormat();

		}

		/**
		 * Scale an image using a percentage
		 *
		 * @static
		 * @access public
		 * @param string $entry The entry name for the record, ex. users
		 * @param string $slug The string representation of the pkey, e.g. 1
		 * @param string $column The name of the column, ex. portrait
		 * @param mixed $percent The percent of the original size to scale to
		 * @return fImage The fImage object representing the image
		 */
		static public function scalePercent($entry = NULL, $slug = NULL, $column = NULL, $percent = NULL, $format = NULL)
		{
			$entry   = fRequest::get('entry', 'string', $entry);
			$slug    = fRequest::get('slug', 'string', $slug);
			$column  = fRequest::get('column', 'string', $column);
			$percent = fRequest::get('percent', 'string', $percent);
			$format  = ($format)
				? $format
				: self::getRequestFormat();

			$percent = ($percent < 1)
				? $percent * 100
				: $percent;

			if ($percent > 100) {
				if (self::checkEntryAction(__CLASS__, __FUNCTION__)) {
					self::triggerError('not_found');
				} else {
					throw new fProgrammerException(
						'Upscaling images is not allowed.'
					);
				}
			}

			self::validateFormat($format);

			try {
				$image = new self($entry, $slug, $column, $format);
			} catch (fException $e) {
				if (self::checkEntry(__CLASS__)) {
					self::trigger('not_found');
				} else {
					throw $e;
				}
			}

			if (!$image->cached) {
				//TODO: Resize the image accordingly and save it
			}

			//TODO: Output the image
		}
	}
