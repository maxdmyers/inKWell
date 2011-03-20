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
		const DEFAULT_IMAGE_QUALITY   = 95;

		/**
		 * Whether or not the request result has been cached
		 *
		 * @access private
		 * @var boolean
		 */
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
		 * The image quality to save cached images with
		 *
		 * @static
		 * @access private
		 * @var integer
		 */
		static private $imageQuality = NULL;

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
			$record       = ActiveRecord::createFromSlug($record_class, $slug);
			$base_name    = $slug .'#' . md5(fURL::get());

			$cache_path   = implode(DIRECTORY_SEPARATOR, array(
				self::$cacheDirectory . $entry,
				fGrammar::pluralize($column),
				$base_name . '.' . $format
			));

			try {
				$cache_directory = dirname($cache_path);
				$cache_directory = new fDirectory($cache_directory);
			} catch (fValidationException $e) {
				fDirectory::create($cache_directory);
			}

			try {
				$this->view   = new fImage($cache_path);
				$this->cached = TRUE;
			} catch (fValidationException $e) {
				$method       = 'get' . fGrammar::camelize($column, TRUE);
				$image        = new fImage($record->$method());
				$image        = $image->duplicate($cache_directory, TRUE);
				$this->view   = $image->rename(implode('.', array(
					$base_name,
					$image->getExtension()
				)), TRUE);
				$this->cached = FALSE;
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

			self::$imageQuality   = (isset($config['image_quality']))
				? $config['image_quality']
				: self::DEFAULT_IMAGE_QUALITY;

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

			try {
				$image = new self($entry, $slug, $column, $format);
			} catch (fException $e) {
				if (self::checkEntry(__CLASS__)) {
					self::triggerError('not_found');
				} else {
					throw $e;
				}
			}

			if (!$image->cached) {
				if (
					(!$width && !height)
					|| $width  > $image->view->getWidth()
					|| $height > $image->view->getHeight()
				) {
					if (self::checkEntry(__CLASS__)) {
						self::triggerError('not_found');
					} else {
						throw new fProgrammerException(
							'Cannot scaled to specified width and/or height'
						);
					}
				}

				if (!$width) {
					$ratio  = $image->view->getHeight() / $height;
					$width  = $image->view->getWidth()  * ratio;
				} elseif (!$height) {
					$ratio  = $image->view->getWidth()  / $width;
					$height = $image->view->getHeight() * $ratio;
				}
				$image->view->resize(intval($width), intval($height));
				if ($format == 'jpg') {
					$image->view->saveChanges($format, self::$imageQuality, TRUE);
				} else {
					$image->view->saveChanges($format, TRUE);
				}
			}

			if (self::checkEntryAction(__CLASS__, __FUNCTION__)) {
				ob_end_clean();
				$image->view->output(TRUE);
			}

			return $image->view;
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

			$percent = ($percent <= 100 || $percent > 1)
				? $percent / 100
				: $percent;

			if ($percent > 1) {
				if (self::checkEntryAction(__CLASS__, __FUNCTION__)) {
					self::triggerError('not_found');
				} else {
					throw new fProgrammerException(
						'Upscaling images is not allowed.'
					);
				}
			}

			try {
				$image = new self($entry, $slug, $column, $format);
			} catch (fException $e) {
				if (self::checkEntry(__CLASS__)) {
					self::triggerError('not_found');
				} else {
					throw $e;
				}
			}

			if (!$image->cached) {
				$width  = $image->view->getWidth()  * $percent;
				$height = $image->view->getHeight() * $percent;

				$image->view->resize(intval($width), intval($height));
				$image->view->saveChanges($format, self::$imageQuality, TRUE);
			}

			if (self::checkEntryAction(__CLASS__, __FUNCTION__)) {
				ob_end_clean();
				$image->view->output(TRUE);
			}

			return $image->view;
		}
	}
