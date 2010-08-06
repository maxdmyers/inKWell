<?php

	/**
	 * Description
	 *
	 * @author Matthew J. Sahagian [mjs] <matt@imarc.net>
	 */
	class View extends fTemplating
	{

		const DEFAULT_VIEW_ROOT     = 'views';

		const MINEX_COMMENTS        = '#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|(//.*)#';
		const MINEX_SPACES          = '#\s+#';
		const MINEX_STRINGS         = '#((?<!//)["\'])(?:\\\1|.)*?\1#';

		// Data storage area, render callbacks, and food?

		private        $data            = array();
		private        $renderCallbacks = array();
		private        $isFood          = FALSE;

		// Directory information for templating engine

		static private $viewRoot        = NULL;
		static private $cacheDirectory  = NULL;

		/**
		 * Creates a new templating object.
		 *
		 * @param string $view_root The root directory for views
		 * @param string $cache_dir A directory where cached views can be stored
		 * @return void
		 */
		public function __construct($view_root = NULL) {

			if ($view_root === NULL) {
				if (self::$viewRoot === NULL) {
					throw new fProgrammerException (
						'No view root has been specified, please call setViewRoot'
					);
				} else {
					parent::__construct(self::$viewRoot);
				}
			} else {
				parent::__construct($view_root);
			}

		}

		/**
		 * Overrides standard get functionality in order to allow a feed
		 * to be created.  Feeds use small areas of output buffered code to
		 * allow inline placement of views.
		 *
		 * @example /sup/documentation/examples/templating/feed-1.inc
		 * @param string $property The name of the property to try and get
		 * @return mixed The result of the magic method, or TRUE/FALSE for feed initialization
		 *
		 */
		public function __get($property)
		{
			switch ($property) {
				case 'feed':
					return ob_start();
				case 'food':
					return ob_get_clean();
				default:
					return parent::__get($property);
			}
		}

		/**
		 * Outputs the view or a particular view element to the screen, this
		 * is a pseudonym for place wrapped to catch exceptions.
		 *
		 * @param string $element An optional name of an element to output
		 * @return void
		 */
		public function render($element = NULL)
		{
			try {
				foreach ($this->renderCallbacks as $callback_info) {
					if (count($callback_info['arguments'])) {
						call_user_func_array(
							$callback_info['method'],
							$callback_info['arguments']
						);
					} else {
						call_user_func($callback_info['method']);
					}
				}
				return $this->place($element);
			} catch (fException $e) {
				echo 'The view cannot be rendered: ' . $e->getMessage();
			}
		}

		/**
		 * Adds a callback to be triggered when the render() method is called.
		 * Keep in mind that rendering has to be done explicitely and that
		 * embedded views are not "rendered", but placed in other views.
		 *
		 * @param callback $callback The callback to be registered
		 * @param mixed $args Each additional parameter is an additional argument for the callback
		 */
		public function onRender($callback)
		{
			if (is_callable($callback)) {
				$this->renderCallbacks[] = array(
					'method'    => $callback,
					'arguments' => array_slice(func_get_args(), 1)
				);
			} else {
				throw new fProgrammerException (
					'Callback must be public or accessible by view.'
				);
			}
		}

		/**
		 * Overrides standard place functionality allowing for feed elements to
		 * be digested and for nested view objects.
		 *
		 * @param string $element The name of the element
		 * @param string $file_type An optional forced file_type (irrelevant for feeds)
		 * @return void
		 */
		public function place($element = NULL, $file_type = NULL)
		{
			if ($element == NULL && $this->isFood) {
				echo $this->get();
			} else {
				return parent::place($element, $file_type);
			}

		}

		/**
		 * Compresses Javascript and CSS files found within $element and
		 * injects the combined script.
		 *
		 * @param string $element The name of the element
		 * @return void
		 */
		 public function compress($element)
		 {

		 	if (self::$cacheDirectory === NULL) {
		 		throw new fProgrammerException (
		 			'The use of compress requires you to set a cache directory.'
		 		);
		 	}

			$file_types = array();

			// Normalize the element

			if ($element = $this->get($element)){
				if (!is_array($element)) {
					$element = array($element);
				}
			} else {
				$element = array();
			}

		 	// Organize the elements by code type and media type

		 	foreach ($element as $file) {

				if (is_array($file) && isset($file['path'])) {
					$path  = $file['path'];
					$media = (isset($file['media'])) ? $file['media'] : 'all';
				} elseif (is_string($file)) {
					$path  = $file;
					$media = 'all';
				} else {
					throw new fProgrammerException(
						'Compression not supported for element %s', $file
					);
				}

				$extension = pathinfo($path, PATHINFO_EXTENSION);

	 			if (strpos($path, '/') === 0) {
	 				$file_types[$extension][$media][] = $_SERVER['DOCUMENT_ROOT'] . $path;
	 			} else {
		 			$file_types[$extension][$media][] = $path;
		 		}
		 	}

			// ID, Compress (if need be), and place/inject.

			foreach($file_types as $extension => $media_types) {
				foreach ($media_types as $media_type => $media_files) {

				 	$cached_id   = md5(implode('::', $media_files));

				 	$cached_path = implode(DIRECTORY_SEPARATOR, array(
						self::$cacheDirectory,        // Cache Directory
						$cached_id . '.' . $extension // Filename
				 	));

					// See if we have a cached version, if not, create it

					try {
						$cached_file = new fFile($cached_path);
						$regenerate  = FALSE;
					} catch (fValidationException $e) {
						$cached_file = fFile::create($cached_path, '');
						$regenerate  = TRUE;
					}

					// Create file objects and look for modified files if
					// the cache is already built.

					$cache_last_modified = $cached_file->getMTime();

					foreach ($media_files as $file) {
						$files[] = $file = new fFile($file);
						if (!$regenerate && $cache_last_modified < $file->getMTime()) {
							$regenerate = TRUE;
						}
					}

					// Regenerate if need be.

					if ($regenerate) {
						$contents = '';
						foreach ($files as $file) {
							$contents .= self::minify($file->read(), $extension);
						}
						$cached_file->write($contents);
					}

					// Inject the cache

					switch ($extension) {
						case 'css':
							$this->set($cached_id, array(array(
								'path'  => $cached_file->getPath(TRUE),
								'media' => $media_type
							)));
							break;
						case 'js':
							$this->set($cached_id, $cached_file->getPath(TRUE));
							break;
					}

					$this->place($cached_id);

				}
			}
		}

		/**
		 * Internally consumes content into an internal element.
		 *
		 * @param string $element The name of the element
		 * @param string $content An optional string of content to consume
		 * @return View The view object to allow for method chaining
		 */
		public function digest($element, $content)
		{
			$view         = new View();
			$view->isFood = TRUE;

			$view->load($content);
			$this->add($element, $view);

			return $this;
		}

		/**
		 * Pack's data into the view's data storage area destroying any
		 * existing keys which may be in it's way
		 *
		 * @param string $data_set A string indicating the data set to pack into
		 * @param mixed $value The value to pack into the data set
		 * @return View The view object to allow for method chaining
		 */
		public function pack ($data_set, $value = NULL)
		{
			if (!is_array($data_set)) {
				if (is_string($data_set) || is_int($data_set)) {
					$data_set = array($data_set => $value);
				} else {
					throw new fProgrammerException(
						'Packed Data requires an array as first parameter if no $value is specified'
					);
				}
			}

			foreach ($data_set as $key => $value) {
				$this->data[$key] = $value;
			}

			return $this;
		}

		/**
		 * Pushes data onto the end of the data storage arrays given keys.
		 * If an element is pushed on which is not an array, it will become one.
		 *
		 * @param string $data_set A string indicating the data set to push into
		 * @param mixed $value The value to push into the data set
		 * @return View The view object to allow for method chaining
		 */
		 public function push ($data_set, $value = NULL)
		 {
			if (!is_array($data_set)) {
				if (is_string($data_set) || is_int($data_set)) {
					$data_set = array($data_set => $value);
				} else {
					throw new fProgrammerException(
						'Pushed Data requires an array as first parameter if no $value is specified'
					);
				}
			}

		 	foreach ($data_set as $key => $value) {
		 		if (!isset($this->data[$key])) {
		 			$this->data[$key] = array();
		 		}
		 		if (!is_array($this->data[$key])) {
		 			$this->data[$key] = array($this->data[$key]);
		 		}
		 		$this->data[$key][]   = $value;
		 	}

		 	return $this;
		 }

		/**
		 * Pulls data referenced by a key from the view's data storage array.
		 * Optionally the data can be destroyed after being pulled and will no
		 * longer be accessible through future calls.
		 *
		 * @param string $key The key of the data to try an pull
		 * @param boolean $destructive Whether or not to destroy the data
		 * @return mixed The pulled data from the data storage array
		 */
		protected function pull($key, $destructive = FALSE)
		{
			if (isset($this->data[$key])) {

				$data = $this->data[$key];

				if ($destructive) {
					unset($this->data[$key]);
				}

				return $data;

			} else {
				throw new fProgrammerException (
					'Cannot pull view data referenced by %s', $key
				);
			}
		}

		/**
		 * Peals data off the end of referenced data storage array.  Optionally
		 * the data can be destroyed.  Please note, that if the data is not
		 * an array this becomes functionally equivalent to pull.
		 *
		 * @param string $key The key of the data from which to pop a value
		 * @param boolean $destructive Whether or not to destroy the data
		 * @return mixed The pealed data from the end of the data storage array
		 */
		protected function peal($key, $destructive = FALSE)
		{
			if (isset($this->data[$key])) {
				if (is_array($this->data[$key])) {

					if ($destructive) {
						return array_pop($this->data[$key]);
					} else {
						return end($this->data[$key]);
					}

				} else {

					return $this->pull($key, $destructive);
				}

			} else {
				throw new fProgrammerException (
					'Cannot peal view data referenced by %s', $key
				);
			}
		}

		/**
		 * Allows for 'selecting' in the view if all data identified
		 * by the keys of $matches contains (if array) or is equal to their
		 * respective value in $matches.
		 *
		 * @param array $matches An array of key (key in data storage) to value (the value to match the data agains) pairs.
		 * @param boolean $as_attribute How to return the resulting selected if all matches are valid
		 * @return string 'selected' or 'selected="selected"' upon matching
		 */
		protected function selectOn(array $matches, $as_attribute = FALSE)
		{
			foreach ($matches as $key => $active_value) {
				$match = FALSE;
				if (isset($this->data[$key])) {
					if (is_array($this->data[$key])) {
						$match = in_array($active_value, $this->data[$key]);
					} else {
						$match = $this->data[$key] == $active_value;
					}
				}
				if (!$match) {
					return '';
				}
			}
			return ($as_attribute) ? 'selected="selected"' : 'selected';
		}

		/**
		 * Allows for 'disabling' in the view if all data identified by
		 * the keys of $matches contains (if array) or is equal to their
		 * respective value in $matches.
		 *
		 * @param array $matches An array of key (key in data storage) to value (the value to match the data agains) pairs.
		 * @param boolean $as_attribute How to return the resulting disabled if all matches are valid
		 * @return string 'disabled' or 'disabled="disabled"' upon matching
		 */
		protected function disableOn(array $matches, $as_attribute = FALSE)
		{
			foreach ($matches as $key => $active_value) {
				$match = FALSE;
				if (isset($this->data[$key])) {
					if (is_array($this->data[$key])) {
						$match = in_array($active_value, $this->data[$key]);
					} else {
						$match = $this->data[$key] == $active_value;
					}
				}
				if (!$match) {
					return '';
				}
			}

			return ($as_attribute) ? 'disabled="disabled"' : 'disabled';
		}

		/**
		 * Allows for 'highlighting' in the view if all data identified
		 * by the keys of $matches contains (if array) or is equal to their
		 * respective value in $matches.
		 *
		 * @param array $matches An array of key (key in data storage) to value (the value to match the data agains) pairs.
		 * @return string 'highlighted' (for use as class) upon matching, an empty string if not
		 */
		protected function highlightOn(array $matches)
		{
			foreach ($matches as $key => $active_value) {
				$match = FALSE;
				if (isset($this->data[$key])) {
					if (is_array($this->data[$key])) {
						$match = in_array($active_value, $this->data[$key]);
					} else {
						$match = $this->data[$key] == $active_value;
					}
				}
				if (!$match) {
					return '';
				}
			}

			return 'highlighted';
		}

		/**
		 * Determines position information about $current_value in the array or
		 * iterator identified by $key in the data storage area.
		 *
		 * @param string $key The key of the iterator in the data storage area
		 * @param mixed $current_value The the current value in the outter loop
		 * @return string A space separated string of position information (first/last and even/odd)
		 */
		protected function positionIn($key, $current_value)
		{
			foreach ($this->data[$key] as $index => $member) {
				if ($current_value === $member) {
					if ($index == 0) {
						$position_info[] = 'first';
					}
					if (($index % 2) == 0) {
						$position_info[] = 'even';
					} else {
						$position_info[] = 'odd';
					}
					if ($index == (sizeof($this->data[$key]) - 1)) {
						$position_info[] = 'last';
					}
					break;
				}
			}

			return implode(' ', $position_info);
		}

		/**
		 * Combines the view element $element together with the separate
		 * provided by $separator.
		 *
		 * @param string $element The name of the element to combine
		 * @param string $separator The string which separates the pieces
		 * @return string The string of combined elements, or an empty string if the element was not an array
		 */
		protected function combine($key, $separator = ' :: ') {
			if (isset($this->data[$key])) {
				if (is_array($this->data[$key])) {
					return implode($separator, $this->data[$key]);
				} else {
					return $this->data[$key];
				}
			}
			return '';
		}

		/**
		 * Reverse combines the view element $element together with the
		 * separator provided by $separator.
		 *
		 * @param string $key The key of the data which to combine
		 * @param string $separator The string which separates the pieces
		 * @return string The string of combined elements, or an empty string if the element was not an array
		 */
		protected function rcombine($key, $separator = ' :: ') {
			if (isset($this->data[$key])) {
				if (is_array($this->data[$key])) {
					return implode($separator, array_reverse($this->data[$key]));
				} else {
					return $this->data[$key];
				}
			}
			return '';
		}

		/**
		 * Initializes the templating engine
		 *
		 * @param array $config The configuration array
		 * @return void

		 */
		static public function __init($config)
		{
			self::setViewRoot(implode(DIRECTORY_SEPARATOR, array(
				$_SERVER['DOCUMENT_ROOT'],
				trim(
					isset($config['view_root'])
					? $config['view_root']
					: self::DEFAULT_VIEW_ROOT
					, '/\\'
				)
			)));

			self::setCacheDirectory(iw::getWriteDirectory('cache'));
		}

		/**
		 * Sets the default view root directory for newly created views.
		 *
		 * @param string $directory The directory to set as the view root
		 * @return void
		 * @throws fEnvironmentException if $directoy is not readable
		 */
		static public function setViewRoot($directory)
		{
			if (is_readable($directory)) {
				self::$viewRoot = new fDirectory($directory);
			} else {
				throw new fProgrammerException (
					'View root directory %s is not readable', $directory
				);
			}
		}

		/**
		 * Sets the cache directory for compressed output or stored views
		 *
		 */
		static public function setCacheDirectory($directory)
		{
			if (is_writable($directory)) {
				self::$cacheDirectory = new fDirectory($directory);
			} else {
				throw new fProgrammerException (
					'Cache directory %s is not writable', $directory
				);
			}
		}

		/**
		 * Minifier for code.  Might not be as thorough as others
		 * but good enough when keeping dependencies down.
		 *
		 * @param string $code The code to minify
		 * @param string $type The type of code it is
		 * @return string The minified version of the code
		 * @throws fProgrammerException if $type is not supported
		 */
		static public function minify($code, $type)
		{
			switch (strtolower($type)) {
				case 'css':
					return self::minifyCSS($code);
				case 'js':
					return self::minifyJS($code);
				default:
					throw new fProgrammerException(
						'Minifying code of type %s is not supported', $type
					);
			}
		}

		/**
		 * Common minification code for C-like syntax.  This will remove
		 * excess white space, and all commenting.  Should work in most
		 * instances.  It's a beast, but that's why we cache.
		 *
		 * @param string $code The code to be minified
		 * @return string The minified code
		 */
		static private function minifyCommon($code)
		{
			preg_match_all(self::MINEX_STRINGS, $code, $matches);
			foreach ($matches[0] as $index => $match) {
				$code = str_replace($match, "%{STR@POS[$index]}%", $code);
			}
			$code = preg_replace(self::MINEX_COMMENTS, '', $code);
			$code = preg_replace(self::MINEX_SPACES,  ' ', $code);
			$code = str_replace('; ', ';', $code);
			$code = str_replace(': ', ':', $code);
			$code = str_replace(' {', '{', $code);
			$code = str_replace('{ ', '{', $code);
			$code = str_replace(', ', ',', $code);
			$code = str_replace('} ', '}', $code);
			$code = str_replace(' }', '}', $code);
			foreach ($matches[0] as $index => $match) {
				$code = str_replace("%{STR@POS[$index]}%", $match, $code);
			}
			return trim($code);
		}

		/**
		 * Minifier for CSS code.
		 *
		 * @param string $code The CSS to minify
		 * @return string The minified CSS
		 */
		static private function minifyCSS($code)
		{
			// Add any CSS Specific minimizations here
			return self::minifyCommon($code);
		}

		/**
		 * Minifier for Javascript code.
		 *
		 * @param string $code The Javascript to minify
		 * @return string The minified Javascript
		 */
		static private function minifyJS($code)
		{
			// Add any Javascript specific minimizations here
			return self::minifyCommon($code);
		}

	}
