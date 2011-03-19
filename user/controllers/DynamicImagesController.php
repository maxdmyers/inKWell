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
		private $imageId   = NULL;
		private $format    = NULL;
		private $image     = NULL;
		private $cacheFile = NULL;
		
		const DEFAULT_CACHE_DIRECTORY = 'cache/images';

		static private $cacheDirectory = NULL;

		/**
		 * Prepares a new DynamicImagesController for running actions.
		 *
		 * @param string void
		 * @return void
		 */
		protected function prepare()
		{
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

			return TRUE;
		}

		/**
		 * Scale an image using specific dimensions.
		 *
		 * @static
		 * @access public
		 * @param integer $width The width to scale to
		 * @param integer $height The height to scale to
		 * @return fImage The fImage object representing the image
		 */
		static public function scale($width = NULL, $height = NULL)
		{
		
		}
		
		/**
		 * Scale an image using a percentage
		 *
		 * @static
		 * @access public
		 * @param mixed $percent The percent of the original size to scale to
		 * @return fImage The fImage object representing the image
		 */
		static public function scalePercent($percent = NULL)
		{
		
		}
	}
