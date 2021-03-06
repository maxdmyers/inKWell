<?php

	/**
	 * IW is the core inKWell class responsible for all shared functionality of all its components.
	 * Tt is a purely static class and is not meant to be instantiated or extended publically.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package inKWell
	 */
	class iw
	{
		const DEFAULT_CONFIG_PATH      = 'config';
		const DEFAULT_CONFIG           = 'default';

		const INITIALIZATION_METHOD    = '__init';
		const MATCH_CLASS_METHOD       = '__match';
		const CONFIG_TYPE_ELEMENT      = '__type';

		const DEFAULT_WRITE_DIRECTORY  = 'assets';
		const DEFAULT_EXECUTION_MODE   = 'development';

		const REGEX_ABSOLUTE_PATH      = '#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//|\./|\.\\\\)#i';
		const REGEX_VARIABLE           = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';

		/**
		 * The cached configuration array
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $config = array();

		/**
		 * The write directory location
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $writeDirectory = NULL;

		/**
		 * The execution mode of inKWell
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $executionMode = NULL;

		/**
		 * The active domain for inKWell
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $activeDomain = NULL;

		/**
		 * The autoloaders list
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $autoLoaders = array();

		/**
		 * Index of classes which have been initialized
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $initializedClasses = array();

		/**
		 * Index of interfaces loaded by the system
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $loadedInterfaces = array();

		/**
		 * The stored failure token
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $failureToken = NULL;

		/**
		 * List of class translations.
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $classTranslations = array();

		/**
		 * Index of configured databases
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $databases = array();

		/**
		 * A list of root directories as registered by classes
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $roots = array();

		/**
		 * Builds a configuration from a series of separate configuration files loaded from a
		 * single directory.  Each configuration key in the final $config array is named after the
		 * file from which it is loaded.  Configuration files should be valid PHP scripts which
		 * return it's local configuration options (for include).
		 *
		 * @static
		 * @access public
		 * @param string|fDirectory $directory The directory containing the configuration elements
		 * @param boolean $quiet Whether or not to output information, defaulted to FALSE
		 * @param array The configuration array which was built
		 */
		static public function buildConfig($directory = NULL, $quiet = FALSE)
		{
			$config = array();

			if ($directory === NULL) {
				$directory = self::getRoot('config');
			} elseif (is_string($directory)) {
				if (!preg_match(self::REGEX_ABSOLUTE_PATH, $directory)) {
					$directory = realpath(implode(DIRECTORY_SEPARATOR, array(
						self::getRoot('config'),
						$directory
					)));
				}
			} elseif (class_exists('fDirectory', FALSE) && $directory instanceof fDirectory) {
				$directory = $directory->getPath();
			}

			if (!is_dir($directory) || !is_readable($directory)) {
				throw new Exception(
					'Cannot build configuration, directory "%s" is unreadable.',
					$directory
				);
			}

			$directory .= DIRECTORY_SEPARATOR;
			//
			// Loads each PHP file into a configuration element named after
			// the file.  We check to see if the CONFIG_TYPE_ELEMENT is set
			// to ensure configurations are added to their respective
			// type in the $config['__types'] array.
			//
			foreach (glob($directory . '*.php') as $config_file) {

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

				$config['__types'][$type][] = $config_element;
				$config[$config_element]    = $current_config;
			}
			//
			// Ensures we recusively scan all directories and merge all
			// configurations.  Directory names do not play a role in the
			// configuration key name.
			//
			foreach (glob($directory . '*', GLOB_ONLYDIR) as $sub_directory) {
				$config = array_merge_recursive(
					$config,
					self::buildConfig($sub_directory, $quiet)
				);
			}

			return $config;
		}

		/**
		 * Creates a configuration array, and sets the config type element to match the specified
		 * $type provided by the user for later use with ::getConfigsByType()
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
		 * Gets the defined/translated class for a particular element name.
		 *
		 * @param string $element The system element to translate
		 * @return string The name of the class which matches the original element.
		 */
		static public function classize($element)
		{
			if (isset(self::$classTranslations[$element])) {
				return self::$classTranslations[$element];
			} else {
				return fGrammar::camelize($element, TRUE);
			}
		}

		/**
		 * Checks the current SAPI name
		 *
		 * @static
		 * @access public
		 * @param string $sapi The SAPI to verify running
		 * @return boolean TRUE if the running SAPI matches, FALSE otherwise
		 */
		static public function checkSAPI($sapi)
		{
			return (strtolower(php_sapi_name()) == strtolower($sapi));
		}

		/**
		 * Returns the active domain for inkwell
		 *
		 * @static
		 * @access public
		 * @param void
		 * @return string The Active domain for inkwell
		 */
		static public function getActiveDomain()
		{
			return self::$activeDomain;
		}

		/**
		 * Get configuration information. If no $element is specified the full inKwell
		 * configuration is returned.  You can specify multiple sub_elements as multiple
		 * parameters.
		 *
		 * @static
		 * @access public
		 * @param string $element The configuration element to get
		 * @param string $sub_element The sub element to get
		 * @param array The configuration array for the requested element
		 */
		static public function getConfig($element = NULL, $sub_element = NULL)
		{
			$config = self::$config;

			if ($element !== NULL) {

				$element = strtolower($element);

				if (isset($config[$element])) {
					$config = $config[$element];
					$params = func_get_args();

					foreach (array_slice($params, 1) as $sub_element) {
						if (isset($config[$sub_element])) {
							$config = $config[$sub_element];
						} else {
							return NULL;
						}
					}

				} else {
					$config = array();
				}
			}

			return $config;
		}

		/**
		 * Get all the configurations matching a certain type.  If one or more sub elements are
		 * defined as additional parameters the returned array will contain only the specific
		 * information for each config element.
		 *
		 * @static
		 * @access public
		 * @param string $type The configuration type
		 * @param string $sub_element The sub element to get
		 * @return array An array of all the configurations matching the type
		 */
		static public function getConfigsByType($type, $sub_element = NULL)
		{
			$type    = strtolower($type);
			$configs = array();

			if (isset(self::$config['__types'][$type])) {
				foreach (self::$config['__types'][$type] as $element) {
					if ($sub_element !== NULL) {

						$params       = func_get_args();
						$sub_elements = array_slice($params, 1);

						array_unshift($sub_elements, $element);

						$configs[$element] = call_user_func_array(
							self::makeTarget(__CLASS__, 'getConfig'),
							$sub_elements
						);
					} else {
						$configs[$element] = self::$config[$element];
					}
				}
			}

			return $configs;
		}

		/**
		 * Gets a database from the stored index of databases.
		 *
		 * @static
		 * @access public
		 * @param string $database_name The database name
		 * @param string $database_role The database role, default 'either'
		 * @return fDatabase The database matching the name and role
		 */
		static public function getDatabase($database_name, $database_role = 'either')
		{
			if ($database_role == 'either' || $database_role == 'write') {
				if (isset(self::$databases[$database_name]['write'])) {
					return self::$databases[$database_name]['write'];
				}
			}

			if ($database_role == 'either' || $database_role == 'read') {
				if (isset(self::$databases[$database_name]['read'])) {
					return self::$databases[$database_name]['read'];
				}
			}

			throw new fNotFoundException (
				'Could not find database %s with role %s',
				$database_name,
				$database_role
			);
		}

		/**
		 * Gets the current execution mode for inkwell
		 *
		 * @static
		 * @access public
		 * @return string The current execution mode
		 */
		static public function getExecutionMode()
		{
			return self::$executionMode;
		}

		/**
		 * Returns a list of available interfaces.  Optionally this will exclude any interfaces
		 * which were added by inKWell (i.e. which didn't exist) in PHP itself.
		 *
		 * @param boolean $native Get only native interfaces, default is FALSE
		 * @return array The list of interfaces
		 */
		static public function getInterfaces($native = FALSE)
		{
			$interfaces = get_declared_interfaces();

			return ($native)
				? array_diff($interfaces, self::$loadedInterfaces)
				: $interfaces;
		}

		/**
		 * Gets a configured root directory from the list of available roots
		 *
		 * @static
		 * @access public
		 * @param string $element The class or configuration element
		 * @return string A reference to the root directory for "live roots"
		 */
		static public function getRoot($element = NULL)
		{
			if ($element === NULL) {
				return APPLICATION_ROOT;
			}

			$element = strtolower($element);

			return (isset(self::$roots[$element]))
				? self::$roots[$element]
				: NULL;
		}

		/**
		 * Gets a write directory.  If the optional parameter is entered it will attempt to get it
		 * as a sub directory of the overall write directory.  If the sub directory does not exist,
		 * it will create it with owner and group writable permissions.
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
				}
				//
				// Prevent an absolute sub directory from repeating the
				// base write directory
				//
				if (strpos($sub_directory, self::$writeDirectory) === 0) {
					$offset        = strlen(self::$writeDirectory);
					$sub_directory = substr($sub_directory, $offset);
				}

				$write_directory = implode(DIRECTORY_SEPARATOR, array(
					self::$writeDirectory,
					trim($sub_directory, '/\\' . DIRECTORY_SEPARATOR)
				));

			} else {
				$write_directory = self::$writeDirectory;
			}

			return (!is_dir($write_directory))
				? fDirectory::create($write_directory)
				: new fDirectory($write_directory);
		}

		/**
		 * Initializes the inKWell system with a chosen configuration.  If not configuraiton is
		 * specified it will result in the default configuration.
		 *
		 * @static
		 * @access public
		 * @param string $configuration The name of the configuration to use
		 * @return array The loaded configuration
		 */
		static public function init($configuration = NULL)
		{
			if (!$configuration) {
				$configuration = self::DEFAULT_CONFIG;
			}

			self::$roots['config'] = realpath(implode(DIRECTORY_SEPARATOR, array(
				self::getRoot(),
				self::DEFAULT_CONFIG_PATH
			)));

			$config_source = implode(DIRECTORY_SEPARATOR, array(
				self::getRoot('config'),
				'.' . $configuration
			));

			if (is_readable($config_source)) {
				self::$config = @unserialize(file_get_contents($config_source));
			}

			if (!self::$config) {
				self::$config = self::buildConfig($configuration, TRUE);
			}
			//
			// Set up our write directory
			//
			self::$writeDirectory = implode(DIRECTORY_SEPARATOR, array(
				self::getRoot(),
				trim(
					isset(self::$config['inkwell']['write_directory'])
						? self::$config['inkwell']['write_directory']
						: self::DEFAULT_WRITE_DIRECTORY,
					'/\\' . DIRECTORY_SEPARATOR
				)
			));
			//
			// Configure our autoloaders
			//
			if (isset(self::$config['autoloaders'])) {
				if(is_array(self::$config['autoloaders'])) {
					self::$autoLoaders = self::$config['autoloaders'];
				}
			}

			spl_autoload_register(self::makeTarget(__CLASS__, 'loadClass'));
			//
			// Set up execution mode
			//
			$valid_execution_modes = array('development', 'production');
			self::$executionMode   = self::DEFAULT_EXECUTION_MODE;

			if (isset(self::$config['inkwell']['execution_mode'])) {
				if (in_array(self::$config['inkwell']['execution_mode'], $valid_execution_modes)) {
					self::$executionMode = self::$config['inkwell']['execution_mode'];
				}
			}
			//
			// Initialize Error Reporting
			//
			if (isset(self::$config['inkwell']['error_level'])) {
				error_reporting(self::$config['inkwell']['error_level']);
			}

			if (isset(self::$config['inkwell']['display_errors'])) {
				if (self::$config['inkwell']['display_errors']) {
					fCore::enableErrorHandling('html');
					fCore::enableExceptionHandling('html', 'time');
					ini_set('display_errors', 1);
				} elseif (isset(self::$config['inkwell']['error_email_to'])) {
					$admin_email = self::$config['inkwell']['error_email_to'];
					fCore::enableErrorHandling($admin_email);
					fCore::enableExceptionHandling($admin_email, 'time');
					ini_set('display_errors', 0);
				} else {
					ini_set('display_errors', 0);
				}
			} elseif (self::getExecutionMode() == 'development') {
				ini_set('display_errors', 1);
			} else {
				ini_set('display_errors', 0);
			}

			//
			// Include any interfaces
			//
			if (isset(self::$config['inkwell']['interfaces'])) {

				$interface_directories = self::$config['inkwell']['interfaces'];

				foreach ($interface_directories as $interface_directory) {
					$files = glob(implode(DIRECTORY_SEPARATOR, array(
						self::getRoot(),
						$interface_directory,
						'*.php'
					)));

					foreach ($files as $file) {

						$interface = pathinfo($file, PATHINFO_FILENAME);

						if (!interface_exists($interface, FALSE)) {
							include $file;
							self::$loadedInterfaces[] = $interface;
						}
					}
				}
			}
			//
			// Initialize Date and Time Information, this has to be before any
			// time related functions.
			//
			fTimestamp::setDefaultTimezone(
				isset(self::$config['inkwell']['default_timezone'])
					? self::$config['inkwell']['default_timezone']
					: 'GMT'
			);

			if (isset(self::$config['inkwell']['date_formats'])) {
				if (is_array($date_formats = self::$config['inkwell']['date_formats'])) {
					foreach ($date_formats as $name => $format) {
						fTimestamp::defineFormat($name, $format);
					}
				}
			}
			//
			// Redirect if we're not the active domain.
			//
			$url_parts          = parse_url(fURL::getDomain());
			self::$activeDomain = isset(self::$config['inkwell']['active_domain'])
				? self::$config['inkwell']['active_domain']
				: $url_parts['host'];

			if (!self::checkSAPI('cli') && $url_parts['host'] != self::$activeDomain) {
				$current_domain = $url_parts['host'];
				$current_scheme = $url_parts['scheme'];
				$current_port   = isset($url_parts['port'])
					? ':' . $url_parts['port']
					: NULL;

				fURL::redirect(
					$current_scheme . '://' . self::$activeDomain . $current_port .
					fURL::getWithQueryString()
				);
			}
			//
			// Initialize the Session
			//
			if (isset(self::$config['inkwell']['session_path'])) {
				fSession::setPath(self::getWriteDirectory(
					self::$config['inkwell']['session_path']
				));
			}

			$session_length = isset(self::$config['inkwell']['session_length'])
				? self::$config['inkwell']['session_length']
				: '30 minutes';

			fSession::setLength($session_length, $session_length);

			if (isset(self::$config['inkwell']['persistent_session'])) {
				if (self::$config['inkwell']['persistent_sessions']) {
					fSession::enablePersistence();
				}
			}

			fSession::open();
			//
			// Initialize the Databases
			//
			$databases_enabled = (
				isset(self::$config['database']['disabled'])
				&& !self::$config['database']['disabled']
			);

			if ($databases_enabled && isset(self::$config['database']['databases'])) {

				if (!is_array(self::$config['database']['databases'])) {
					throw new fProgrammerException (
						'Databases must be configured as an array.'
					);
				}

				$databases = self::$config['database']['databases'];

				foreach ($databases as $name => $settings) {

					$database_target = explode('::', $name);

					$database_entry   = !empty($database_target[0])
						? $database_target[0]
						: NULL;

					$database_role   = isset($database_target[1])
						? $database_target[1]
						: 'both';

					if (!is_array($settings)) {
						throw new fProgrammerException (
							'Database settings must be configured as an array.'
						);
					}

					$database_type = (isset($settings['type']))
						? $settings['type']
						: NULL;

					$database_name = (isset($settings['name']))
						? $settings['name']
						: NULL;


					if (!isset($database_type) || !isset($database_name)) {
						throw new fProgrammerException (
							'Database support requires a type and name.'
						);
					}

					$database_user = (isset($settings['user']))
						? $settings['user']
						: NULL;

					$database_password = (isset($settings['password']))
						? $settings['password']
						: NULL;

					$database_hosts = (isset($settings['hosts']))
						? $settings['hosts']
						: NULL;

					if (is_array($database_hosts) && count($database_hosts)) {

						$target = self::makeTarget(__CLASS__, 'database_host['. $name . ']');

						if (!($stored_host = fSession::get($target, NULL))) {

							$host_index    = array_rand($database_hosts);
							$database_host = $database_hosts[$host_index];

							fSession::set($target, $database_host);

						} else {

							$database_host = $stored_host;
						}
					}

					if (strpos($database_host, 'sock:') !== 0) {
						$host_parts    = explode(':', $database_host, 2);
						$database_host = $host_parts[0];
						$database_port = (isset($host_parts[1]))
							? $host_parts[1]
							: NULL;
					} else {
						$database_port = NULL;
					}

					$db = new fDatabase(
						$database_type,
						$database_name,
						$database_user,
						$database_password,
						$database_host,
						$database_port
					);

					if (!in_array($database_role, array('read', 'write', 'both'))) {
						throw new fProgrammerException (
							'Cannot add database %s, invalid role %s',
							$database_name,
							$databaseb_role
						);
					}

					if ($database_role == 'read'  || $database_role == 'both') {
						self::$databases[$database_name]['read'] = $db;
					}

					if ($database_role == 'write' || $database_role == 'both') {
						self::$databases[$database_name]['write'] = $db;
					}

					fORMDatabase::attach($db, $database_entry, $database_role);
				}
			}
			//
			// Load the Scaffolder if we have a configuration for it
			//
			if (isset(self::$config['scaffolder'])) {
				self::loadClass('Scaffolder');
			}
			//
			// All other configurations have the following special properties
			//
			// 'class'          => Signifies which class the configuration maps to
			// 'preload'        => Signifies that the class should be preloaded
			// 'root_directory' => Used by the scaffolder and more
			//
			//
			$preload_classes = array();

			foreach (self::$config as $element => $config) {

				$core = self::$config['__types']['core'];

				if ($element !== '__types' && !in_array($element, $core)) {

					if (isset($config['class'])) {
						$class = self::$classTranslations[$element] = $config['class'];
					} else {
						//
						// Default convention is upper camelcase
						//
						self::$classTranslations[$element] = fGrammar::camelize($element, TRUE);
						$class                             = self::$classTranslations[$element];
					}

					if (isset($config['root_directory'])) {
						self::$roots[$element] = $config['root_directory'];
					}

					if (isset($config['auto_load'])	&& $config['auto_load']) {
						if (isset(self::$roots[$element]) && self::$roots[$element]) {
							self::$config['autoloaders'][$class] = self::$roots[$element];
						}
					}

					if (isset($config['preload']) && $config['preload']) {
						$preload_classes[] = $class;
					}
				}
			}

			foreach ($preload_classes as $class) {
				self::loadClass($class);
			}

			return self::$config;
		}

		/**
		 * Initializes a class by calling it's __init() method if it has one and returning its
		 * return value.
		 *
		 * @static
		 * @access protected
		 * @param string $class The class to initialize
		 * @return bool Whether or not the initialization was successful
		 */
		static protected function initializeClass($class)
		{
			//
			// Classes cannot be initialized twice
			//
			if (in_array($class, self::$initializedClasses)) {
				return TRUE;
			}

			$init_callback = array($class, self::INITIALIZATION_METHOD);
			//
			// If there's no __init we're done
			//
			if (!is_callable($init_callback)) {
				return TRUE;
			}

			$method  = end($init_callback);
			$rmethod = new ReflectionMethod($class, $method);
			//
			// If __init is not custom, we're done
			//
			if ($rmethod->getDeclaringClass()->getName() != $class) {
				return TRUE;
			}

			// Determine class configuration and call __init with it
			$element      = fGrammar::underscorize($class);
			$class_config = (isset(self::$config[$element]))
				? self::$config[$element]
				: array();

			try {
				if (call_user_func($init_callback, $class_config, $element)) {
					self::$initializedClasses[] = $class;
					return TRUE;
				}
			} catch (Exception $e) {}

			return FALSE;
		}

		/**
		 * The inKWell conditional autoloader which allows for auto loading based on dynamic class
		 * name matches.
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

				if (strpos($test, '*') !== FALSE) {
					$regex = str_replace('*', '(.*?)', str_replace('\\', '\\\\', $test));
					$match = preg_match('/' . $regex . '/', $class);
				} elseif (class_exists($test)) {
					$test  = self::makeTarget($test, self::MATCH_CLASS_METHOD);
					$match = is_callable($test)
						? call_user_func($test, $class)
						: FALSE;
				} else {
					$match = TRUE;
				}

				if (class_exists($class, FALSE)) {
					//
					// Recursion may have loaded the class at this point, so we may not need to go
					// any further.
					//
					return TRUE;

				} elseif ($match) {
					//
					// But maybe we do...
					//
					$file = implode(DIRECTORY_SEPARATOR, array(
						self::getRoot(),
						//
						// Trim leading or trailing directory separators from target
						//
						trim($target, '/\\' . DIRECTORY_SEPARATOR),
						//
						// Replace any backslashes in the class with directory separator
						// to support Namespaces and trim the leading root namespace if present.
						//
						ltrim(str_replace('\\', DIRECTORY_SEPARATOR, $class), '\\') . '.php'
					));

					if (file_exists($file)) {

						include $file;

						if (is_array($interfaces = class_implements($class, FALSE))) {
							return (in_array('inkwell', $interfaces))
								? self::initializeClass($class)
								: TRUE;
						}
					}
				}
			}

			return FALSE;
		}

		/**
		 * Creates a target identifier from an entry and action.  If the entry consists of the
		 * term 'link' then the action is treated as a URL.
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
			if (!is_callable($target) && strpos($target, '*') !== 0) {

				$query = (count($query))
					? '?' . @http_build_query($query, '', '&', PHP_QUERY_RFC3986)
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
		 * Writes a full configuration array out to a particular file.
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $file The file to write to, optional if overwriting the default config
		 * @param boolean $quiet Whether or not to output information, defaulted to FALSE
		 * @return mixed Number of bytes written to file or FALSE on failure
		 */
		static public function writeConfig(array $config, $file = NULL, $quiet = FALSE)
		{
			if (!$file) {
				$file = '.' . self::DEFAULT_CONFIG;
			}

			if (!preg_match(self::REGEX_ABSOLUTE_PATH, $file)) {
				$file = realpath(self::getRoot('config') . DIRECTORY_SEPARATOR . $file);
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
		 * Construction not possible
		 *
		 * @final
		 * @access private
		 * @param void
		 * @return void
		 */
		final private function __construct() {}
	}
