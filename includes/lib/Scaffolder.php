<?php

	if (self::$config['scaffolder']['disabled']) { return; }

	/**
	 * Description
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class Scaffolder
	{
	
		const DEFAULT_SCAFFOLDING_ROOT  = 'scaffolding';

		// Configuration Informations

		static private $scaffoldingRoot = NULL;

		/**
		 * Initializses the Scaffolder class
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
			if (isset($config['scaffolding_root'])) {
				self::setScaffoldingRoot($_SERVER['DOCUMENT_ROOT'] . $config['scaffolding_root']);
			} else {
				self::setScaffoldingRoot($_SERVER['DOCUMENT_ROOT'] . self::DEFAULT_SCAFFOLDING_ROOT);
			}
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
		 * Scaffolds a new class using the parent class template.
		 *
		 * @param string $class The new class name to scaffold as
		 * @param string $parent_class The parent class to copy
		 * @param array $support_vars An associative array of variables to import for scaffolding
		 * @return string The Templated Class
		 */
		static public function makeClass($class, $parent_class, $support_vars = array(), $scaffolding = FALSE)
		{
			ob_start();
			extract($support_vars);
			include implode(DIRECTORY_SEPARATOR, array(
				self::$scaffoldingRoot,
				'classes',
				$parent_class . '.php'
			));
			return ob_get_clean();
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
