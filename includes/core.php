<?php

	/**
	 * IW is the core inKWell class responsible for all shared functionality
	 * of all it's components.  It is a purely static class and is not meant
	 * to be instantiated or extended.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class iw
	{

		const DEFAULT_CONFIG_DIR        = 'config';
		const DEFAULT_CONFIG_FILE       = 'config.php';

		const INITIALIZATION_METHOD     = '__init';
		const MATCH_CLASS_METHOD        = '__match';

		const DEFAULT_REQUEST_FORMAT    = 'html';
		const DEFAULT_WRITE_DIRECTORY   = 'writable';

		static private $config          = array();
		static private $writeDirectory  = NULL;
		static private $staticALMatches = array();

		/**
		 * Constructing an iw object is not allowed, this is purely for
		 * namespacing and static controls.
		 */
		final private function __construct()
		{
		}

		/**
		 * Creates a configuration array, but allows the user to typecast the
		 * configuration for use with iw::getConfigsByType()
		 *
		 * @param string $type The configuration type
		 * @return array The configuration array
		 */
		static public function createConfig($type, $config)
		{
			$config['__type'] = strtolower($type);
			return $config;
		}

		/**
		 * Builds a configuration from a series of separate configuration
		 * files loaded from a root directory.  Each configuration file is
		 * named after its key in the final $config and is a valid PHP script
		 * which returns (from include) it's local configuration options.
		 *
		 * @param string $directory The directory containing the configuration elements
		 * @param boolean $queit Whether or not to output information
		 * @param array The configuration array which was built
		 */
		static public function buildConfig($directory = NULL, $quiet = FALSE)
		{
			$config = array();

			if (!$directory) { $directory = self::DEFAULT_CONFIG_DIR;  }

			if (is_dir($directory) && is_readable($directory)) {

				$current_working_directory = getcwd();

				chdir($directory);

				foreach (glob("*.php") as $config_file) {

					$config_element = pathinfo($config_file, PATHINFO_FILENAME);

					if (!$quiet) {
						echo "Loading config data for $config_element...\n";
					}

					$current_config = include($config_file);

					if (isset($current_config['__type'])) {
						$type = $current_config['__type'];
						unset($current_config['__type']);
					} else {
						$type = $config_element;
					}

					$config['types'][$type][] = $config_element;
					$config[$config_element]  = $current_config;
				}

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
		 * @param array $config The configuration array
		 * @param string $file The file to write to
		 * @return mixed Number of bytes written to file or FALSE on failure
		 */
		static public function writeConfig(array $config, $file = NULL)
		{
			if (!$file) { $file = self::DEFAULT_CONFIG_FILE; }

			echo "Writing configuration file...";

			if ($result = file_put_contents($file, serialize($config))) {
				echo "Success!";
			} else {
				echo "Failure.";
			}
			return $result;
		}

		/**
		 * Initializes the inKWell system with a configuration
		 *
		 * @param array $config_file The location of the config file
		 * @return void
		 */
		static public function init($config_file = NULL)
		{

			if (!$config_file) { $config_file = self::DEFAULT_CONFIG_FILE; }

			if (is_readable($config_file)) {
				$config = @unserialize(file_get_contents($config_file));
			} else {
				$config = FALSE;
			}

			if (!$config) {
				$config = @self::buildConfig(NULL, $config_file, TRUE);
			}

			self::$writeDirectory = implode(DIRECTORY_SEPARATOR, array(
				$_SERVER['DOCUMENT_ROOT'],
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
		 * @param string $config_element The configuration element to get
		 * @param array The configuration array for the requested element
		 */
		static public function getConfig($config_element = NULL)
		{
			$config = self::$config;

			if ($config_element !== NULL) {
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
		 * @param string $sub_directory The optional sub directory to return.
		 */
		static public function getWriteDirectory($sub_directory = NULL)
		{
			if ($sub_directory) {
				$write_directory = implode(DIRECTORY_SEPARATOR, array(
					self::$writeDirectory,
					trim($sub_directory, '/\\')
				));
			} else {
				$write_directory = self::$writeDirectory;
			}

			if(!is_writable($write_directory)) {
				try {
					fDirectory::create($write_directory, 0775);
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
		 * @param string $entry The class representing the entry
		 * @param string $method The method representing the action
		 * @return string An inKWell target string
		 */
		static public function makeTarget($entry, $action)
		{
			if ($entry == 'link') {
				return $action;
			}

			return implode('::', array($entry, $action));
		}

		/**
		 * The inKWell conditional autoloader which allows for auto loading
		 * based on dynamic class name matches.
		 *
		 * @param string $class The class to be loaded
		 * @param array $loaders An array of test => target autoloaders
		 * @return mixed Whether or not the class was successfully loaded and initialized
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

				if ($match) {

					$file = implode(DIRECTORY_SEPARATOR, array(
						$_SERVER['DOCUMENT_ROOT'],   // Document Root
						trim($target, '/\\'),        // Target directory
						$class . '.php'              // Class name as PHP file
					));

					if (file_exists($file)) {
						include $file;
						return self::initializeClass($class);
					}
				}
			}

			return FALSE;
		}

		/**
		 * Initializes a class by calling it's __init() method if it has one
		 * and returning its return value.
		 *
		 * @param string $class The class to initialize
		 * @return mixed The return value of the __init function, usually boolean
		 */
		static protected function initializeClass($class)
		{
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
