<?php

	/**
	 * Description
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell
	 */
	class View extends fTemplating implements inkwell
	{

		const DEFAULT_VIEW_ROOT     = 'views';
		const DEFAULT_CACHE_DIR     = 'cache';
		const PRIMARY_VIEW_ELEMENT  = '__main__';

		// Data storage area, render callbacks, and food?

		private        $data               = array();
		private        $renderCallbacks    = array();
		private        $isFood             = FALSE;

		// Information for templating engine

		static private $viewRoot         = NULL;
		static private $cacheDirectory   = NULL;
		static private $minificationMode = NULL;

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
						'No view root has been specified, please call %s',
						iw::makeTarget(__CLASS__, 'setViewRoot()')
					);
				} else {
					parent::__construct(self::$viewRoot);
				}
			} else {
				parent::__construct($view_root);
			}

			if (self::$minificationMode) {
				$this->enableMinification(self::$minificationMode, self::$cacheDirectory);
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
		 * @param boolean $return Whether or not to return output as a string
		 * @return void
		 */
		public function render($element = NULL, $return = FALSE)
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

				if (!$return) {
					return $this->place($element);
				} else {
					ob_start();
					$this->place($element);
					return ob_get_clean();
				}

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
		 * Sets a file as the "master" template to be included when/if place
		 * is called without arguments.
		 *
		 * @param string $file
		 * @return fTemplating The template object, to allow for method chaining
		 */
		public function load($file)
		{
			$this->set(self::PRIMARY_VIEW_ELEMENT, $file);
			return $this;
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
			if ($element === NULL) {
				$element = self::PRIMARY_VIEW_ELEMENT;
			}

			// Digested elements are views whose primary element is content to
			// be echoed instead of a file to be included.

			if ($this->isFood) {
				echo $this->get($element);
			} else {
				return parent::place($element, $file_type);
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
						'No value provided for scalar data set.'
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
						'No data provided for scalar data set.'
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
			if (array_key_exists($key, $this->data)) {

				$data = $this->data[$key];

				if ($destructive) {
					unset($this->data[$key]);
				}

				return $data;

			} else {
				throw new fProgrammerException (
					'Data referenced by %s does not exist.', $key
				);
			}
		}

		/**
		 * Peels data off the end of referenced data storage array.  Optionally
		 * the data can be destroyed.  Please note, that if the data is not
		 * an array this becomes functionally equivalent to pull.
		 *
		 * @param string $key The key of the data from which to pop a value
		 * @param boolean $destructive Whether or not to destroy the data
		 * @return mixed The peeled data from the end of the data storage array
		 */
		protected function peel($key, $destructive = FALSE)
		{
			if (array_key_exists($key, $this->data)) {
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
					'Data referenced by %s does not exist.', $key
				);
			}
		}

		/**
		 * Verifies/Checks view data.  View data is checked by ensuring that
		 * all elements identified by the keys of $matches equal their
		 * respective value in $matches, or if the value is an array, whether
		 * or not the value identified by the element/key is contained in the
		 * array.
		 *
		 * @param array $matches An array of key (data element) to value (to match against) pairs.
		 * @return boolean TRUE if the data element identified by the key matches or is contained in the value.
		 */
		protected function check(array $matches)
		{
			foreach ($matches as $key => $active_value) {
				$match = FALSE;
				if (array_key_exists($key, $this->data)) {
					if (is_array($this->data[$key])) {
						$match = in_array($active_value, $this->data[$key]);
					} else {
						$match = $this->data[$key] == $active_value;
					}
				}
				if (!$match) {
					return FALSE;
				}
			}
			return TRUE;
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
			if ($this->check($matches)) {
				return ($as_attribute) ? 'selected="selected"' : 'selected';
			} else {
				return '';
			}
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
			if ($this->check($matches)) {
				return ($as_attribute) ? 'disabled="disabled"' : 'disabled';
			} else {
				return '';
			}
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
			if ($this->check($matches)) {
				return 'highlighted';
			} else {
				return '';
			}
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
			$position_info = array();

			if (array_key_exists($key, $this->data)) {
				if (
					$this->data[$key] instanceof Traversable ||
					is_array($this->data[$key])
				) {
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
			if (array_key_exists($key, $this->data)) {
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
			if (array_key_exists($key, $this->data)) {
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

			self::$viewRoot = implode(DIRECTORY_SEPARATOR, array(
				$_SERVER['DOCUMENT_ROOT'],
				trim(
					isset($config['view_root'])
					? $config['view_root']
					: self::DEFAULT_VIEW_ROOT
					, '/\\'
				)
			));

			try {
				self::$viewRoot = new fDirectory(self::$viewRoot);
			} catch (fValidationException $e) {
				throw new fProgrammerException (
					'View root directory %s is not readable',
					self::$viewRoot
				);
			}

			self::$cacheDirectory = iw::getWriteDirectory(
				isset($config['cache_directory'])
					? $config['cache_directory']
					: self::DEFAULT_CACHE_DIRECTORY
			);

			try {
				self::$cacheDirectory = new fDirectory(self::$cacheDirectory);
			} catch (fValidationException $e) {
				throw new fProgrammerException (
					'Cache directory %s does not exist',
					self::$cacheDirectory
				);
			}

			if (!self::$cacheDirectory->isWritable()) {
				throw new fProgrammerException (
					'Cache directory %s is not writable',
					self::$cacheDirectory
				);
			}

			if (isset($config['minification_mode'])) {
				if ($config['minification_mode']) {
					self::$minificationMode = $config['minification_mode'];
				}
			}
		}

		/**
		 * Check whether or not a particular view file exists.
		 *
		 * @param string $view_file The relative path to the view file to check
		 */
		static public function exists($view_file)
		{
			if (strpos(self::$viewRoot, $view_file) !== 0) {
				$view_file = implode(DIRECTORY_SEPARATOR, array(
					self::$viewRoot,
					ltrim($view_file, '/\\')
				));
			}

			return is_readable($view_file);
		}

	}
