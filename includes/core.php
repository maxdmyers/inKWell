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
		const INITIALIZATION_METHOD   = '__init';
		const DEFAULT_REQUEST_FORMAT  = 'html';
		const DEFAULT_WRITE_DIRECTORY = 'writable';

		static private $config                = array();
		static private $writeDirectory        = NULL;

		/**
		 * Constructing an iw object is not allowed, this is purely for
		 * namespacing and static controls.
		 */
		final private function __construct()
		{
		}

		/**
		 * Initializes the inKWell system with a configuration
		 *
		 * @param array $config The array of configuration information
		 * @return void
		 */
		static public function init(array $config)
		{
			self::$writeDirectory = implode(DIRECTORY_SEPARATOR, array(
				$_SERVER['DOCUMENT_ROOT'],
				trim(
					isset($config['global']['write_directory'])
					? $config['global']['write_directory']
					: self::DEFAULT_WRITE_DIRECTORY
					, '/\\'
				)
			));

			if (
				isset($config['global']['disable_scaffolder']) &&
				!$config['global']['disable_scaffolder']       &&
				isset($config['scaffolder']['autoloaders'])    &&
				is_array($config['scaffolder']['autoloaders'])
			) {

				$config['autoloaders'] = array_merge(
					$config['autoloaders'],
					$config['scaffolder']['autoloaders']
				);
			}

			spl_autoload_register('iw::loadClass');

			self::$config = $config;
		}

		/**
		 * The inKWell conditional autoloader which allows for auto loading
		 * via directories and callbacks on matched conditions.
		 *
		 * @param string $class The class to be loaded
		 * @return void
		 */
		static public function loadClass($class)
		{
			foreach (self::$config['autoloaders'] as $test => $target) {

				if (is_callable($test)) {
					$match = call_user_func($test, $class);
				} elseif($test == ($regex = str_replace('*', '(.*?)', $test))) {
					$match = TRUE;
				} else {
					$match = preg_match('#' . $regex . '#', $class);
				}

				if ($match) {

					if (is_callable($target)) {
						if (!call_user_func($target, $class)) {
							continue;
						}
					} else {

						$file       = implode(DIRECTORY_SEPARATOR, array(
							$_SERVER['DOCUMENT_ROOT'],   // Document ROot
							trim($target, '/\\'),        // Target directory
							$class . '.php'              // Class name as PHP file
						));

						if (file_exists($file)) {
							include $file;
						} else {
							continue;
						}
					}

					$init_callback = array($class, self::INITIALIZATION_METHOD);

					// If there's no __init we're done
					if (!is_callable($init_callback)) {
						return;
					}

					$method  = end($init_callback);
					$rmethod = new ReflectionMethod($class, $method);

					// If __init is not custom, we're done
					if ($rmethod->getDeclaringClass()->getName() != $class) {
						return;
					}

					// Determine class configuration and call __init with it
					$config_index = fGrammar::underscorize($class);
					$class_config = (isset(self::$config[$config_index]))
						? self::$config[$config_index]
						: array();

					return call_user_func($init_callback, $class_config);
				}
			}
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
		 		$message = 'Directory %s is not writable';
				try {
					fDirectory::create($write_directory, 0770);
				} catch (fException $e) {
					throw new fEnvironmentException(
						$message . ' and we were unable to create it.', $write_directory
					);
		 		}
			}
			return new fDirectory($write_directory);
		}

		/**
		 * Creates a target string to uniquely identify an entry point.
		 *
		 * @param string|object $class The object or class of the controller or view
		 * @param string $method The method which will retrieve and handle it
		 * @return string The recpient string for use with fMessaging
		 */
		 static public function makeTarget($class, $method)
		 {
		 	return implode('::', array($class, $method));
		 }


	}
