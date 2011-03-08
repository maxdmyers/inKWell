<?php

	/**
	 * IW is the core inKWell class responsible for all shared functionality
	 * of all it's components.  It is a purely static class and is not meant
	 * to be instantiated or extended.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell
	 */
	class iw
	{

		const DEFAULT_CONFIG_DIR      = 'config';
		const DEFAULT_CONFIG_FILE     = 'config.php';

		const INITIALIZATION_METHOD   = '__init';
		const MATCH_CLASS_METHOD      = '__match';
		const CONFIG_TYPE_ELEMENT     = '__type';

		const DEFAULT_WRITE_DIRECTORY = 'writable';

		const VARIABLE_REGEX          = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

		static private $config             = array();
		static private $writeDirectory     = NULL;
		static private $staticALMatches    = array();
		static private $initializedClasses = array();
		static private $failureToken       = NULL;

		/**
		 * Constructing an iw object is not allowed, this is purely for
		 * namespacing and static controls.
		 *
		 * @final
		 * @access private
		 * @param void
		 * @return void
		 */
		final private function __construct()
		{
		}

		/**
		 * Creates a configuration array, and sets the config type element to
		 * match the specified $type provided by the user for later use with
		 * iw::getConfigsByType()
		 *
		 * @static
		 * @access public
		 * @param string $type The configuration type
		 * @return array The configuration array
		 */
		static public function createConfig($type, $config)
		{
			$config[self::CONFIG_TYPE_ELEMENT] = strtolower($type);
			return $config;
		}

		/**
		 * Builds a configuration from a series of separate configuration
		 * files loaded from a single directory.  Each configuration key in the
		 * final $config array is named after the file from which it is loaded.
		 * Configuration files should be valid PHP scripts which return
		 * it's local configuration options (for include).
		 *
		 * @static
		 * @access public
		 * @param string $directory The directory containing the configuration elements
		 * @param boolean $quiet Whether or not to output information
		 * @param array The configuration array which was built
		 */
		static public function buildConfig($directory = NULL, $quiet = FALSE)
		{
			$config = array();

			if (!$directory) {
				$directory = self::DEFAULT_CONFIG_DIR;
			}

			if ($directory instanceof fDirectory) {
				$directory = $directory->getPath();

			} elseif (!is_dir($directory) || !is_readable($directory)) {
				throw new fProgrammerException (
					'Unable to build configuration, directory %s is not readable.',
					$directory
				);

			} else {

				$current_working_directory = getcwd();

				chdir($directory);

				// Loads each PHP file into a configuration element named after
				// the file.  We check to see if the CONFIG_TYPE_ELEMENT is set
				// to ensure configurations are added to their respective
				// type in the $config['types'] array.

				foreach (glob("*.php") as $config_file) {

					$config_element = pathinfo($config_file, PATHINFO_FILENAME);

					if (!$quiet) {
						echo "Loading config data for $config_element...\n";
					}

					$current_config = include($config_file);

					if (isset($current_config[self::CONFIG_TYPE_ELEMENT])) {
						$type = $current_config[self::CONFIG_TYPE_ELEMENT];
						unset($current_config[self::CONFIG_TYPE_ELEMENT]);
					} else {
						$type = $config_element;
					}

					$config['types'][$type][] = $config_element;
					$config[$config_element]  = $current_config;
				}

				// Ensures we recusively scan all directories and merge all
				// configurations.  Directory names do not play a role in the
				// configuration key name.

				foreach (glob("*", GLOB_ONLYDIR) as $sub_directory) {
					$config = array_merge_recursive(
						$config,
						self::buildConfig($sub_directory, $quiet)
					);
				}

				chdir($current_working_directory);

			}

			return $config;
		}

		/**
		 * Writes a full configuration array out to a particular file.
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $file The file to write to
		 * @param boolean $quiet Whether or not to output information
		 * @return mixed Number of bytes written to file or FALSE on failure
		 */
		static public function writeConfig(array $config, $file = NULL, $quiet = FALSE)
		{
			if (!$file) {
				$file = self::DEFAULT_CONFIG_FILE;
			}

			if (!$quiet) {
				echo "Writing configuration file...";
			}

			$result = file_put_contents($file, serialize($config));

			if (!$quiet) {
				echo ($result) ? 'Sucess!' : 'Failure';
			}

			return $result;
		}

		/**
		 * Initializes the inKWell system with a configuration
		 *
		 * @static
		 * @access public
		 * @param string $config_file The location of the config file
		 * @return void
		 */
		static public function init($config_file = NULL)
		{

			if (!$config_file) {
				$config_file = realpath(self::DEFAULT_CONFIG_FILE);
			}

			if (is_readable($config_file)) {
				$config = @unserialize(file_get_contents($config_file));
			} else {
				$config = FALSE;
			}

			if (!$config) {
				$config = @self::buildConfig(NULL, $config_file, TRUE);
			}

			self::$writeDirectory = implode(DIRECTORY_SEPARATOR, array(
				APPLICATION_ROOT,
				trim(
					isset($config['inkwell']['write_directory'])
					? $config['inkwell']['write_directory']
					: self::DEFAULT_WRITE_DIRECTORY
					, '/\\'
				)
			));

			if (isset($config['autoloaders'])) {
				if(!is_array($config['autoloaders'])) {
					throw new fProgrammerException (
						'Autoloaders must be configured as an array.'
					);
				}
				spl_autoload_register('iw::loadClass');
			}

			return (self::$config = $config);
		}

		/**
		 * Get configuration information. If no $config_element is specified
		 * the full inKwell configuration is returned.
		 *
		 * @static
		 * @access public
		 * @param string $config_element The configuration element to get
		 * @param array The configuration array for the requested element
		 */
		static public function getConfig($config_element = NULL)
		{
			$config = self::$config;

			if ($config_element !== NULL) {

				$config_element = strtolower($config_element);

				if (isset($config[$config_element])) {
					$config = $config[$config_element];
				} else {
					$config = array();
				}
			}

			return $config;
		}

		/**
		 * Get all the configurations matching a certain type.
		 *
		 * @static
		 * @access public
		 * @param string $type The configuration type
		 * @return array An array of all the configurations matching the type
		 */
		static public function getConfigsByType($type)
		{
			$type    = strtolower($type);
			$configs = array();

			if (isset(self::$config['types'][$type])) {
				foreach (self::$config['types'][$type] as $config_element) {
					$configs[$config_element] = self::$config[$config_element];
				}
			}

			return $configs;
		}

		/**
		 * Gets the requested write directory.  If the optional parameter is
		 * entered it will attempt to get it as a sub directory of the overall
		 * write directory.  If the child directory does not exist, if the sub
		 * directory does not exist, it will create it with owner and group
		 * writable permissions.
		 *
		 * @static
		 * @access public
		 * @param string|fDirectory $sub_directory The optional sub directory to return.
		 * @return fDirectory The writable directory object
		 */
		static public function getWriteDirectory($sub_directory = NULL)
		{
			if ($sub_directory) {

				if ($sub_directory instanceof fDirectory) {
					$sub_directory = $sub_directory->getPath();

				} elseif (!is_string($sub_directory)) {
					throw new fProgrammerException(
						'Sub directory must be a string or fDirectory object'
					);
				}

				// Prevent an absolute sub directory from repeating the
				// base write directory

				if (strpos($sub_directory, self::$writeDirectory) === 0) {
					$offset        = strlen(self::$writeDirectory);
					$sub_directory = substr($sub_directory, $offset);
				}

				$write_directory = implode(DIRECTORY_SEPARATOR, array(
					self::$writeDirectory,
					trim($sub_directory, '/\\')
				));

			} else {
				$write_directory = self::$writeDirectory;
			}

			if(!is_writable($write_directory)) {
				try {
					fDirectory::create($write_directory);
				} catch (fException $e) {
					throw new fEnvironmentException(
						'Directory %s is not writable or createable.',
						$write_directory
					);
		 		}
			}

			return new fDirectory($write_directory);
		}

		/**
		 * Creates a target identifier from an entry and action.  If the entry
		 * consists of the term 'link' then the action is treated as a URL.
		 *
		 * @static
		 * @access public
		 * @param string $entry A string representation of an entry type
		 * @param string $action A string representation of an action supported by the entry
		 * @return string An inKWell target
		 */
		static public function makeTarget($entry, $action)
		{
			if ($entry == 'link') {
				return $action;
			}

			return implode('::', array($entry, $action));
		}

		/**
		 * Get a link to to a controller target
		 *
		 * @static
		 * @access public
		 * @param string $target an inKWell target to redirect to
		 * @param array $query an associative array containing parameters => values
		 * @return string The appropriate URL for the provided parameters
		 */
		static public function makeLink($target, $query = array())
		{
			if (!is_callable($target)) {

				$query = (count($query))
					? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986)
					: NULL;

				if (strpos($target, '/') === 0 && Moor::getActiveProxyURI()) {
					return Moor::getActiveProxyURI() . $target . $query;
				}

				return $target . $query;
			}

			$params = array_keys($query);

			$target = (array_unshift($params, $target) == 1)
				? $target
				: implode(' ', $params);

			return call_user_func_array(
				'Moor::linkTo',
				array_merge(array($target), $query)
			);
		}

		/**
		 * Creates a unique failure token which can then be checked with
		 * checkFailureToken().
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return string A unique failure token for immediate use
		 */
		static public function makeFailureToken()
		{
			return (self::$failureToken = fCryptography::randomString(8));
		}

		/**
		 * Checks the unique failure token against the stored value
		 *
		 * @static
		 * @access public
		 * @param string $failure_token The failure token to check
		 * @return boolean TRUE if the failure token matches, FALSE otherwise
		 */
		static public function checkFailureToken($failure_token)
		{
			return (self::$failureToken === $failure_token);
		}

		/**
		 * Checks whether or not a variable name is eval() safe
		 *
		 * @static
		 * @access public
		 * @param string $variable The variable name to check
		 * @return boolean TRUE if the variable name is eval() safe, FALSE otherwise
		 */
		static public function isEvalSafe($variable)
		{
			return preg_match('#' . self::VARIABLE_REGEX . '#', $variable);
		}

		/**
		 * The inKWell conditional autoloader which allows for auto loading
		 * based on dynamic class name matches.
		 *
		 * @static
		 * @access public
		 * @param string $class The class to be loaded
		 * @param array $loaders An array of test => target autoloaders
		 * @return boolean Whether or not the class was successfully loaded and initialized
		 */
		static public function loadClass($class, array $loaders = array())
		{

			if (!count($loaders)) {
				$loaders = self::$config['autoloaders'];
			}

			foreach ($loaders as $test => $target) {

				$match = $test;

				if (!in_array($test, self::$staticALMatches)) {

					if ($test == $class) {

						// TODO: This needs to be thought about.  The default
						// TODO: autoloaders are putting, library, as a static
						// TODO: key, since it it doesn't contain a * the
						// TODO: it recursively autoloads on the class_exists
						// TODO: This prevents the recursive autoload from
						// TODO: going any further than the key itself to try
						// TODO: and load it... would one of the loaders below
						// TODO: hypothetically be responsible for matching and
						// TODO: targetting a class used as a key above?

						return;

					} elseif (strpos($test, '*') !== FALSE) {

						$regex = str_replace('*', '(.*?)', $test);
						$match = preg_match('/' . $regex . '/', $class);

					} elseif (class_exists($test)) {

						$test  = self::makeTarget(
							$test,
							self::MATCH_CLASS_METHOD
						);

						if (is_callable($test)) {
							$match = call_user_func($test, $class);
						}
					}

					if ($test == $match) {
						self::$staticALMatches[] = $test;
					}
				}

				if ($match !== FALSE) {

					$file = implode(DIRECTORY_SEPARATOR, array(
						APPLICATION_ROOT,
						trim($target, '/\\'),        // Target directory
						$class . '.php'              // Class name as PHP file
					));

					if (file_exists($file)) {

						include $file;

						$interfaces = class_implements($class, FALSE);

						return (in_array('inkwell', $interfaces))
							? self::initializeClass($class)
							: TRUE;
					}
				}
			}

			return FALSE;
		}

		/**
		 * Initializes a class by calling it's __init() method if it has one
		 * and returning its return value.
		 *
		 * @static
		 * @access protected
		 * @param string $class The class to initialize
		 * @return mixed The return value of the __init function, usually boolean
		 */
		static protected function initializeClass($class)
		{

			// Classes cannot be initialized twice
			if (in_array($class, self::$initializedClasses)) {

				throw new fProgrammerException(
					'The class %s has already been initialized.', $class
				);

			} else {

				self::$initializedClasses[] = $class;
			}

			$init_callback = array($class, self::INITIALIZATION_METHOD);

			// If there's no __init we're done
			if (!is_callable($init_callback)) {
				return TRUE;
			}

			$method  = end($init_callback);
			$rmethod = new ReflectionMethod($class, $method);

			// If __init is not custom, we're done
			if ($rmethod->getDeclaringClass()->getName() != $class) {
				return TRUE;
			}

			// Determine class configuration and call __init with it
			$config_index = fGrammar::underscorize($class);
			$class_config = (isset(self::$config[$config_index]))
				? self::$config[$config_index]
				: array();

			return call_user_func($init_callback, $class_config);
		}

	}

	/**
	 * The inKWell interface is used to determine whether or not a class will
	 * support the following methods:
	 *
	 *  __init()
	 *  __match()
	 */

	interface inkwell {}
