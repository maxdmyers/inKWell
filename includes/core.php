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

		const DEFAULT_CONFIG          = 'config';

		const INITIALIZATION_METHOD   = '__init';
		const MATCH_CLASS_METHOD      = '__match';
		const CONFIG_TYPE_ELEMENT     = '__type';

		const DEFAULT_WRITE_DIRECTORY = 'writable';

		const REGEX_VARIABLE          = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';
		const REGEX_ABS_PATH          = '#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//|\./|\.\\\\)#i';

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
		 * Cached static auto-loader matches
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $staticALMatches = array();

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
		 * @param string|fDirectory $directory The directory containing the configuration elements
		 * @param boolean $quiet Whether or not to output information
		 * @param array The configuration array which was built
		 */
		static public function buildConfig($directory = NULL, $quiet = FALSE)
		{
			$config = array();

			if (!$directory) {
				$directory = self::DEFAULT_CONFIG;
			}

			if ($directory instanceof fDirectory) {

				$directory = $directory->getPath();

			} elseif (!is_dir($directory) || !is_readable($directory)) {
				throw new Exception (sprintf(
					'Cannot built configuration, directory %s is unreadable.',
					$directory
				));

			}

			$current_working_directory = getcwd();

			chdir($directory);

			// Loads each PHP file into a configuration element named after
			// the file.  We check to see if the CONFIG_TYPE_ELEMENT is set
			// to ensure configurations are added to their respective
			// type in the $config['__types'] array.

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

				$config['__types'][$type][] = $config_element;
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
				$file = self::DEFAULT_CONFIG . '.php';
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
		 * @param string $configuration The name of the configuration to use
		 * @return void
		 */
		static public function init($configuration = NULL)
		{

			if (!$configuration) {
				$configuration = self::DEFAULT_CONFIG;
			}

			$file   = realpath($configuration . '.php');
			$config = (is_readable($file))
				? @unserialize(file_get_contents($file))
				: NULL;

			if (!$config) {
				$config = @self::buildConfig($configuration, TRUE);
			}

			self::$config          = $config;
			self::$roots['config'] = realpath($configuration);

			// Set up the inkwell root directory

			if (isset(self::$config['inkwell']['root_directory'])) {
				self::$roots['inkwell'] = rtrim(
					$config['inkwell']['root_directory'],
					'/\\'
				);
			} else {
				self::$roots['inkwell'] = APPLICATION_ROOT;
			}

			// Set up our write directory

			self::$writeDirectory = implode(DIRECTORY_SEPARATOR, array(
				iw::getRoot(),
				trim(
					isset(self::$config['inkwell']['write_directory'])
						? self::$config['inkwell']['write_directory']
						: self::DEFAULT_WRITE_DIRECTORY,
					'/\\'
				)
			));

			// Configure our autoloaders

			if (isset(self::$config['autoloaders'])) {
				if(!is_array(self::$config['autoloaders'])) {
					throw new fProgrammerException (
						'Autoloaders must be configured as an array.'
					);
				}
			} else {
				self::$config['autoloaders'] = array();
			}

			spl_autoload_register('iw::loadClass');

			// Initialize Error Reporting

			if (isset(self::$config['inkwell']['error_level'])) {
				error_reporting(self::$config['inkwell']['error_level']);
			}

			if (isset(self::$config['inkwell']['display_errors'])) {
				if (self::$config['inkwell']['display_errors']) {
					fCore::enableErrorHandling('html');
					fCore::enableExceptionHandling('html');
					ini_set('display_errors', 1);
				} elseif (isset(self::$config['inkwell']['error_email_to'])) {
					$admin_email = self::$config['inkwell']['error_email_to'];
					fCore::enableErrorHandling($admin_email);
					fCore::enableExceptionHandling($admin_email);
					ini_set('display_errors', 0);
				} else {
					ini_set('display_errors', 0);
				}
			}

			// Include any interfaces

			if (isset(self::$config['inkwell']['interfaces'])) {

				$interface_directories = self::$config['inkwell']['interfaces'];

				foreach ($interface_directories as $interface_directory) {
					$files = glob(implode(DIRECTORY_SEPARATOR, array(
						iw::getRoot(),
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

			// Initialize Date and Time Information, this has to be before any
			// time related functions.

			if (isset(self::$config['inkwell']['default_timezone'])) {
				fTimestamp::setDefaultTimezone(
					self::$config['inkwell']['default_timezone']
				);
			} else {
				throw new fProgrammerException(
					'Please configure your timezone'
				);
			}

			if (
				   isset(self::$config['inkwell']['date_formats'])
				&& is_array(self::$config['inkwell']['date_formats'])
			) {
				$date_formats = self::$config['inkwell']['date_formats'];
				foreach ($date_formats as $name => $format) {
					fTimestamp::defineFormat($name, $format);
				}
			}

			// Initialize the Session

			fSession::setPath(
				iw::getWriteDirectory(implode(DIRECTORY_SEPARATOR, array(
					'.tmp',
					'sessions'
				)))
			);

			$session_length = isset(self::$config['inkwell']['session_length'])
				? $config['inkwell']['session_length']
				: '30 minutes';

			if (isset(self::$config['inkwell']['persistent_session'])
				&& self::$config['inkwell']['persistent_sessions']
			) {
				fSession::enablePersistence();
				fSession::setLength($session_length, $session_length);
			} else {
				fSession::setLength($session_length);
			}
			fSession::open();

			// Initialize the Databases

			if (isset(self::$config['database']['disabled'])
				&& !self::$config['database']['disabled']
				&& isset($config['database']['databases'])
			)  {

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

						$target = iw::makeTarget('iw', 'db_host['. $name . ']');

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

					iw::addDatabase($db = new fDatabase(
						$database_type,
						$database_name,
						$database_user,
						$database_password,
						$database_host,
						$database_port
					), $database_entry, $database_role);

					fORMDatabase::attach($db, $database_entry, $database_role);
				}
			}

			// Load the Scaffolder if we have a configuration for it

			if (isset(self::$config['scaffolder'])) {
				iw::loadClass('Scaffolder');
			}

			// All other configurations have the following special properties
			//
			// 'class'          => Signifies which class the configuration maps to
			// 'preload'        => Signifies that the class should be preloaded
			// 'root_directory' => Used by the scaffolder and more
			//

			$preload_classes = array();

			foreach (self::$config as $element => $config) {

				$core = self::$config['__types']['core'];

				if ($element !== '__types' && !in_array($element, $core)) {

					if (isset($config['class'])) {
						fGrammar::addCamelUnderscoreRule(
							$config['class'],
							$element
						);
					}

					$class = fGrammar::camelize($element, TRUE);

					if (isset($config['root_directory'])) {
						self::$roots[$element] = $config['root_directory'];
					}

					if (isset($config['auto_load'])
						&& $config['auto_load']
						&& isset(self::$roots[$element])
					) {
						self::addAutoLoader($class, self::$roots[$element]);
					}

					if (isset($config['preload']) && $config['preload']) {
						$preload_classes[] = $class;
					}
				}
			}

			foreach ($preload_classes as $class) {
				iw::loadClass($class);
			}

			return self::$config;
		}

		/**
		 * Returns a list of available interfaces.  Optionally this will exclude
		 * any interfaces which were added by inKWell (i.e. which didn't exist)
		 * in PHP itself.
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
		 * Get configuration information. If no $element is specified
		 * the full inKwell configuration is returned.  You can specify
		 * multiple sub_elements as multiple parameters.
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
		 * Get all the configurations matching a certain type.  If one or more
		 * sub elements are defined as additional parameters the returned array
		 * will contain only the specific information for each config element.
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
							'iw::getConfig',
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
		 * Gets a configured root directory for the list of available roots
		 *
		 * @static
		 * @access public
		 * @param string $element The class or configuration element
		 * @return string A reference to the root directory for "live roots"
		 */
		static public function getRoot($element = 'inkwell')
		{
			$element = strtolower($element);

			return (isset(self::$roots[$element]))
				? self::$roots[$element]
				: NULL;
		}

		/**
		 * Gets a database from the stored index of databases.
		 *
		 * @static
		 * @access public
		 * @param string $db_name The database name
		 * @param string $db_role The database role, default 'either'
		 * @return fDatabase The database matching the name and role
		 */
		static public function getDatabase($db_name, $db_role = 'either')
		{
			if ($db_role == 'either' || $db_role == 'write') {
				if (isset(self::$databases[$db_name]['write'])) {
					return self::$databases[$db_name]['write'];
				}
			}

			if ($db_role == 'either' || $db_role == 'read') {
				if (isset(self::$databases[$db_name]['read'])) {
					return self::$databases[$db_name]['read'];
				}
			}

			throw new fNotFoundException (
				'Could not find database %s with role %s',
				$db_name,
				$db_role
			);
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
			if (strpos($target, '*::') === 0) {
				$target = Moor::getActiveClass() . substr($target, 1);
			}

			if (strpos($target, '*\\') === 0 || preg_match('/^\*_[A-Z][A-Za-z0-9]*::/', $target)) {
				$target = Moor::getActiveNamespace() . substr($target, 1);
			}

			if (!is_callable($target)) {

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
						iw::getRoot(),
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
			$element      = fGrammar::underscorize($class);
			$class_config = (isset(self::$config[$element]))
				? self::$config[$element]
				: array();

			return call_user_func($init_callback, $class_config, $element);
		}

		/**
		 * Adds a database to the database index for retrieval with
		 * getDatabase() method.
		 *
		 * @static
		 * @access private
		 * @param fDatabase $db The database object
		 * @param string $db_name The name of the database
		 * @param string $db_role The role of the database
		 * @return void
		 */
		static private function addDatabase(fDatabase $db, $db_name, $db_role)
		{
			if (!in_array($db_role, array('read', 'write', 'both'))) {
				throw new fProgrammerException (
					'Cannot add database %s, invalid role %s',
					$db_name,
					$db_role
				);
			}

			if ($db_role == 'read' || $db_role == 'both') {
				self::$databases[$db_name]['read'] = $db;
			}

			if ($db_role == 'write' || $db_role == 'both') {
				self::$databases[$db_name]['write'] = $db;
			}
		}

		/**
		 * Adds an autoloader to the autoloaders configuration key
		 *
		 * @static
		 * @access private
		 * @param string A match string compatible with iw::loadClass()
		 * @param string A target to load from
		 * @return void
		 */
		static private function addAutoLoader($match, $target) {
			self::$config['autoloaders'][$match] = $target;
		}

	}
