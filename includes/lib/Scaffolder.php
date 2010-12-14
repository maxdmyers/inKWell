<?php

	/**
	 * The inKWell scaffolder
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class Scaffolder extends iw implements inkwell
	{

		const DEFAULT_SCAFFOLDING_ROOT = 'scaffolding';
		const DYNAMIC_SCAFFOLD_METHOD  = '__make';
		const FINAL_SCAFFOLD_METHOD    = '__scaffold';

		const VARIABLE_REGEX           = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';

		// Configuration Informations

		static private $scaffoldingRoot = NULL;
		static private $config          = array();

		/**
		 * Initializses the Scaffolder class
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init(array $config = array())
		{
			self::$config = $config;

			if (isset($config['disabled']) && $config['disabled']) {
				return FALSE;
			}

			self::setScaffoldingRoot(implode(DIRECTORY_SEPARATOR, array(
				$_SERVER['DOCUMENT_ROOT'],
				trim(
					isset($config['scaffolding_root'])
					? $config['scaffolding_root']
					: self::DEFAULT_SCAFFOLDING_ROOT
					, '/\\'
				)
			)));

			if (isset($config['output_map'])) {
				if (!is_array($config['output_map'])) {
					throw new fProgrammerException (
						'Scaffolder output_map must be configured as an array.'
					);
				}
				spl_autoload_register(iw::makeTarget(__CLASS__, 'loadClass'));
			}

			return TRUE;
		}

		/**
		 * Attempts to load a class via Scaffolder
		 *
		 * @param string $class The class to be loaded
		 * @param array $output_map The output map array of $class => $target members
		 * @return mixed Whether or not the class was successfully loaded and initialized
		 */
		static public function loadClass($class, array $output_map = array())
		{
			if (!count($output_map)) {
				$output_map = self::$config['output_map'];
			}

			foreach ($output_map as $loader => $target) {

				$test = iw::makeTarget($loader, self::MATCH_CLASS_METHOD);
				$make = iw::makeTarget($loader, self::DYNAMIC_SCAFFOLD_METHOD);

				if (is_callable($test) && is_callable($make)) {
					if (call_user_func($test, $class)) {
						if (call_user_func($make, $class)) {

							return self::initializeClass($class);
						}
					}
				}
			}

			return FALSE;
		}

		/**
		 * Sets the scaffolding root directory where all scaffolding templates
		 * are located
		 *
		 * @param string $directory The directory containing the scaffolding
		 * @return void
		 */
		static protected function setScaffoldingRoot($directory)
		{
			if (is_readable($directory)) {
				self::$scaffoldingRoot = new fDirectory($directory);
			} else {
				throw new fProgrammerException (
					'Scaffolding root directory %s is not readable', $directory
				);
			}
		}

		/**
		 *
		 * Examples:
		 *
		 */
		static public function build($class, $target, $support_vars = array())
		{
			if (class_exists($class)) {

				$make_method = iw::makeTarget($class, self::FINAL_SCAFFOLD_METHOD);

				if (is_callable($make_method)) {
					if (call_user_func($make_method, $target, $support_vars)) {

					} else {
						throw new fProgrammerException (
							'Scaffolding failed, %s cannot build %s',
							$make_method,
							$target
						);
					}
				} else {
					throw new fProgrammerException (
						'Scaffolding failed, %s does not support %s',
						$class,
						self::FINAL_SCAFFOLD_METHOD
					);
				}
			} else {
				throw new fProgrammerException (
					'Scaffolding failed, %s is an unknown class',
					$class
				);
			}
		}

		/**
		 * Scaffolds a new class using the parent class template.
		 *
		 * @param string $class The new class name to scaffold as
		 * @param string $parent_class The parent class to copy
		 * @param array $support_vars An associative array of variables to import for scaffolding
		 * @return string The Templated Class
		 */
		static public function makeClass($class, $parent_class, $support_vars = array(), $scaffolding = FALSE)
		{
			$is_safe  = (
				preg_match('#' . self::VARIABLE_REGEX . '#', $class) &&
				preg_match('#' . self::VARIABLE_REGEX . '#', $parent_class)
			);

			if ($is_safe && extract($support_vars) == sizeof($support_vars)) {

				$scaffolding_template = implode(DIRECTORY_SEPARATOR, array(
					self::$scaffoldingRoot,
					'classes',
					$parent_class . '.php'
				));

				if (!is_readable($scaffolding_template)) {
					throw new fProgrammerException(
						'Scaffolder cannot make class %s, no template found', $class
					);
				} else {
					ob_start();
					include $scaffolding_template;
					return ob_get_clean();
				}

			} else {
				throw new fProgrammerException(
					'Scaffolder detected insecure or invalid class or variable names'
				);
			}
		}

		/**
		 * Writes a scaffolded class out to the filesystem
		 *
		 * @param string $file The location of the file to contain the scaffolded code
		 * @param string $class The new class name to scaffold as
		 * @param string $parent_class The parent class to copy
		 * @param array $template_vars An associative array of variables to import for templating
		 * @return boolean TRUE if the file was written, FALSE otherwise
		 */
		static public function writeClass($file, $class, $parent_class, $template_vars = array())
		{
			return file_put_contents($file,
				'<?php' . "\n\n" . self::makeClass($class, $parent_class, $template_vars, TRUE)
			);
		}

		/**
		 * Exports variables in the same sense as var_export(), however does
		 * some cleanup for arrays and other types.
		 *
		 * @param mixed $variable The variable to export
		 * @return string A PHP parseable version of the variable
		 */
		static private function export_var($variable)
		{
			$translated = var_export($variable, TRUE);
			$translated = str_replace("\n", '', $translated);
			if (is_array($variable)) {
				$translated = preg_replace('# (\d+) => #', '', $translated);
				$translated = str_replace(',)', ')', $translated);
				$translated = str_replace('( ', '(', $translated);
				$translated = str_replace('array ', 'array', $translated);
			}
			return $translated;
		}

	}
