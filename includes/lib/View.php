<?php

	/**
	 * The view class is responsible for interfacing Controllers with actual
	 * view files / templates.
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

		/**
		 * The data storage area
		 *
		 * @access private
		 * @var array
		 */
		private $data = array();

		/**
		 * A list of callbacks to call when render() is called
		 *
		 * @access private
		 * @var array
		 */
		private $renderCallbacks = array();

		/**
		 * If the template represents string content
		 *
		 * @access private
		 * @var boolean
		 */
		private $isFood = FALSE;

		/**
		 * The path from which relative views are loaded
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $viewRoot = NULL;

		/**
		 * The path where cached views and compressed styles/scripts are stored
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $cacheDirectory = NULL;

		/**
		 * The minification mode for compressing styles/scripts
		 *
		 * @static
		 * @access private
		 * @var string|NULL
		 */
		static private $minificationMode = NULL;

		/**
		 * Creates a new templating object.
		 *
		 * @access public
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
				$this->enableMinification(
					self::$minificationMode,
					self::$cacheDirectory
				);
			}

		}

		/**
		 * Outputs the view or a particular view element to the screen, this
		 * is a pseudonym for place wrapped to catch exceptions and allow for
		 * callbacks.
		 *
		 * @access public
		 * @param string $element An optional name of an element to output
		 * @param boolean $return Whether or not to return output as a string
		 * @return void|string Void if $return is FALSE, the rendered template as a string otherwise
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
		 * @access public
		 * @param callback $callback The callback to be registered
		 * @param mixed $args Each additional parameter is an additional argument for the callback
		 * @return void
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
		 * Loads a view file.  If the file begins with a '/' it will be looked
		 * for relative to the document root.  If the file does not it will be
		 * relative to the configured view root.  If the first parameter
		 * may be an array of files, of which, the first one to exist will be
		 * used.
		 *
		 * @access public
		 * @param string|array $file The path to the view file or an array of candidate files
		 * @return View The view object, to allow for method chaining
		 */
		public function load($file)
		{
			if (is_array($file)) {
				foreach ($file as $candidate_file) {
					if (self::exists($candidate_file)) {
						$file = $candidate_file;
						break;
					}
				}
			}

			$this->set(self::PRIMARY_VIEW_ELEMENT, $file);
			return $this;
		}

		/**
		 * Places a view or view element.
		 *
		 * @access public
		 * @param string $element The name of the element
		 * @param string $file_type An optional forced file_type (irrelevant for feeds)
		 * @return void
		 */
		public function place($element = NULL, $file_type = NULL)
		{
			if ($element === NULL) {
				$element = self::PRIMARY_VIEW_ELEMENT;
			}

			if ($this->isFood) {
				echo $this->get($element);
			} else {
				return parent::place($element, $file_type);
			}
		}

		/**
		 * Adds string content to a view element, such that when the element
		 * is placed the content is directly outputted.  In short, this is
		 * for non-data elements which do not have template files.  If the
		 * second parameter is NULL content will be loaded directly into the
		 * view.
		 *
		 * @access public
		 * @param string $element The name of the element, or content for primary element
		 * @param string $content Content to consume for a specific element
		 * @return View The view object to allow for method chaining
		 */
		public function digest($element, $content = NULL)
		{
			if ($content === NULL) {
				$this->isFood = TRUE;
				$this->load($element);
			} else {
				$view         = new self();
				$view->isFood = TRUE;
				$view->load($content);
				$this->add($element, $view);
			}

			return $this;
		}

		/**
		 * Pack's data into the view's data storage area destroying any
		 * existing keys which may be in it's way
		 *
		 * @access public
		 * @param string $data_set A string indicating the data set to pack into
		 * @param mixed $value The value to pack into the data set
		 * @return View The view object to allow for method chaining
		 */
		public function pack($data_set, $value = NULL)
		{
			if (!is_array($data_set)) {
				if (is_string($data_set) || is_int($data_set)) {
					$data_set = array($data_set => $value);
				} else {
					throw new fProgrammerException(
						'Invalid data set supplied, must be a string or integer'
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
		 * @access public
		 * @param string $data_set A string indicating the data set to push into
		 * @param mixed $value The value to push into the data set
		 * @return View The view object to allow for method chaining
		 */
		 public function push($data_set, $value = NULL)
		 {
			if (!is_array($data_set)) {
				if (is_string($data_set) || is_int($data_set)) {
					$data_set = array($data_set => $value);
				} else {
					throw new fProgrammerException(
						'Invalid data set supplied, must be a string or integer'
					);
				}
			}

		 	foreach ($data_set as $key => $value) {
		 		if (!array_key_exists($key, $this->data)) {
		 			$this->data[$key] = array();
		 		} elseif (!is_array($this->data[$key])) {
		 			$this->data[$key] = array($this->data[$key]);
		 		}

		 		$this->data[$key][] = $value;
		 	}

		 	return $this;
		 }

		/**
		 * Pulls data referenced by a key from the view's data storage array.
		 * Optionally the data can be destroyed after being pulled and will no
		 * longer be accessible through future calls.
		 *
		 * @access public
		 * @param string $key The key of the data to try an pull
		 * @param mixed $default The default value if the key is not found
		 * @param boolean $destructive Whether or not to destroy the data
		 * @return mixed The pulled data from the data storage array
		 */
		public function pull($key, $default = NULL, $destructive = FALSE)
		{
			if (array_key_exists($key, $this->data)) {

				$data = $this->data[$key];

				if ($destructive) {
					unset($this->data[$key]);
				}

				return $data;

			}  elseif (func_num_args() > 1) {
				return $default;
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
		 * @access public
		 * @param string $key The key of the data from which to pop a value
		 * @param mixed $default The default value if the key is not found
		 * @param boolean $destructive Whether or not to destroy the data
		 * @return mixed The peeled data from the end of the data storage array
		 */
		public function peel($key, $default = NULL, $destructive = FALSE)
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

			} elseif (func_num_args() > 1) {
				return $default;
			} else {
				throw new fProgrammerException (
					'Data referenced by %s does not exist.', $key
				);
			}
		}

		/**
		 * Iterates over an element and outputs a provided partial or callable
		 * emitter for each child element.  If the emitter is a callback it
		 * can accept up to two arguments, the first being the child element
		 * during each call, and the second being the current view.  If the
		 * emitter is an array, the key is used as the child element variable
		 * within the partial, while the value is the view partial.
		 *
		 * Examples:
		 *
		 * $this->repeat('users', array('user' => 'partials/user.php'));
		 *
		 * $this->repeat('users', function($user, $this){
		 *		echo $user->prepareName();
		 * })
		 *
		 * @acces public
		 * @param string $key The key of the data which to repeat
		 * @param array|callback $emitter The function or partial which will be emitted
		 * @return View The view object to allow for method chaining
		 */
		public function repeat($key, $emitter)
		{
			if (array_key_exists($key, $this->data)) {
				if (
					$this->data[$key] instanceof Traversable
					|| is_array($this->data[$key])
				) {

					if (is_callable($emitter)) {

						foreach ($this->data[$key] as $value) {
							$emitter($value, $this);
						}

					} elseif (is_array($emitter)) {

						$element = key($emitter);

						if (!preg_match(iw::REGEX_VARIABLE, $element)) {
							throw new fProgrammerException (
								'Array emitter key must be valid variable name.'
							);
						}

						$partial = reset($emitter);

						if (!self::exists($partial)) {
							throw new fProgrammerException (
								'The partial %s is unreadable',
								$partial
							);
						}

						foreach ($this->data[$key] as $$element) {
							if (!preg_match(iw::REGEX_ABS_PATH, $partial)) {
								include implode(DIRECTORY_SEPARATOR, array(
									self::$viewRoot,
									$partial
								));
							} else {
								include $partial;
							}
						}

					} else {
						throw new fProgrammerException (
							'Invalid data type for emitter, must be %s',
							fGrammar::joinArray(array(
								'a callback',
								'an array'
							),  'or')
						);
					}
				}
			}
		}

		/**
		 * Verifies/Checks view data.  View data is checked by ensuring that
		 * all elements identified by the keys of $matches equal their
		 * respective value in $matches, or if the value is an array, whether
		 * or not the value identified by the element/key is contained in the
		 * array.
		 *
		 * @access public
		 * @param array $matches An array of key (data element) to value (to match against) pairs.
		 * @return boolean TRUE if the data element identified by the key matches or is contained in the value.
		 */
		public function check(array $matches)
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
		 * @access public
		 * @param array $matches An array of key (key in data storage) to value (the value to match the data agains) pairs.
		 * @param boolean $as_attribute How to return the resulting selected if all matches are valid
		 * @return string 'selected' or 'selected="selected"' upon matching
		 */
		public function selectOn(array $matches, $as_attribute = FALSE)
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
		 * @access public
		 * @param array $matches An array of key (key in data storage) to value (the value to match the data agains) pairs.
		 * @param boolean $as_attribute How to return the resulting disabled if all matches are valid
		 * @return string 'disabled' or 'disabled="disabled"' upon matching
		 */
		public function disableOn(array $matches, $as_attribute = FALSE)
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
		 * @access public
		 * @param array $matches An array of key (key in data storage) to value (the value to match the data agains) pairs.
		 * @return string 'highlighted' (for use as class) upon matching, an empty string if not
		 */
		public function highlightOn(array $matches)
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
		 * @access public
		 * @param string $key The key of the iterator in the data storage area
		 * @param mixed $current_value The the current value in the outter loop
		 * @return string A space separated string of position information (first/last and even/odd)
		 */
		public function positionIn($key, $current_value)
		{
			$position_info = array();

			if (array_key_exists($key, $this->data)) {
				if (
					$this->data[$key] instanceof Traversable
					|| is_array($this->data[$key])
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
		 * @access public
		 * @param string $element The name of the element to combine
		 * @param string $separator The string which separates the pieces
		 * @return string The string of combined elements, or an empty string if the element was not an array
		 */
		public function combine($key, $separator = ' :: ') {
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
		 * @access public
		 * @param string $key The key of the data which to combine
		 * @param string $separator The string which separates the pieces
		 * @return string The string of combined elements, or an empty string if the element was not an array
		 */
		public function rcombine($key, $separator = ' :: ') {
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
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return void
		 */
		static public function __init(array $config = array(), $element = NULL)
		{

			self::$viewRoot = implode(DIRECTORY_SEPARATOR, array(
				iw::getRoot(),
				($root_directory = iw::getRoot($element))
					? $root_directory
					: self::DEFAULT_SCAFFOLDING_ROOT
			));

			self::$viewRoot       = new fDirectory(self::$viewRoot);
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
		 * Check whether or not a particular view file exists relative to the
		 * view root.
		 *
		 * @static
		 * @access public
		 * @param string $view_file The relative path to the view file to check
		 * @return boolean TRUE if the view file is readable, FALSE otherwise
		 */
		static public function exists($view_file)
		{
			if ($view_file[0] !== '/' && $view_file[0] !== '\\') {
				$view_file = implode(DIRECTORY_SEPARATOR, array(
					self::$viewRoot,
					$view_file
				));
			} else {
				$view_file = implode(DIRECTORY_SEPARATOR, array(
					$_SERVER['DOCUMENT_ROOT'],
					$view_file
				));
			}

			return is_readable($view_file);
		}

	}
