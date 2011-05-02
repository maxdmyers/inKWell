<?php

	/**
	 * The inKWell scaffolder
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell
	 */
	class Scaffolder extends iw implements inkwell
	{

		const DEFAULT_SCAFFOLDING_ROOT = 'scaffolding';
		const DYNAMIC_SCAFFOLD_METHOD  = '__make';
		const FINAL_SCAFFOLD_METHOD    = '__build';

		/**
		 * The directory containing scaffolding templates
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $scaffoldingRoot = NULL;

		/**
		 * Whether or not we are in the process of building
		 *
		 * @static
		 * @access private
		 * @var boolean
		 */
		static private $isBuilding = FALSE;

		/**
		 * A list of classes to auto-scaffold
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $autoScaffoldClasses = array();

		/**
		 * Contains the last scaffolded code
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $lastScaffoldedCode = NULL;

		/**
		 * Initializses the Scaffolder class
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return void
		 */
		static public function __init(array $config = array(), $element = NULL)
		{
			if (isset($config['disabled']) && $config['disabled']) {
				return FALSE;
			}

			self::$scaffoldingRoot = implode(DIRECTORY_SEPARATOR, array(
				iw::getRoot(),
				($root_directory = iw::getRoot($element))
					? $root_directory
					: self::DEFAULT_SCAFFOLDING_ROOT
			));

			self::$scaffoldingRoot = new fDirectory(self::$scaffoldingRoot);
			$register_autoloader   = FALSE;

			foreach (iw::getConfig() as $element => $element_config) {
				if (isset($element_config['auto_scaffold'])) {
					if ($element_config['auto_scaffold']) {
						self::$autoScaffoldClasses[] = fGrammar::camelize(
							$element,
							TRUE
						);
						$register_autoloader = TRUE;
					}
				}
			}

			if ($register_autoloader) {
				spl_autoload_register(iw::makeTarget(__CLASS__, 'loadClass'));
			}

			return TRUE;
		}

		/**
		 * Attempts to load a class via Scaffolder, i.e. performs on the fly
		 * scaffolding.
		 *
		 * @static
		 * @access public
		 * @param string $class The class to be loaded
		 * @return mixed Whether or not the class was successfully loaded and initialized
		 */
		static public function loadClass($class)
		{
			foreach (self::$autoScaffoldClasses as $loader) {

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
		 * Builds and writes out the scaffolding for a configured builder.
		 * The builder can be a class or a template.  If a template is passed
		 * it is assumed to require no additional template variables.
		 *
		 * @static
		 * @access public
		 * @param string $builder A valid class or template name
		 * @param string $target A valid class name or file location
		 * @return void
		 */
		static public function build($builder, $target)
		{
			self::$isBuilding = TRUE;

			try {

				$root_directory = rtrim(implode(DIRECTORY_SEPARATOR, array(
					iw::getRoot(),
					iw::getRoot(fGrammar::underscorize($builder))
				)), '/\\');

				if (is_string($target)) {
					$output_file = implode(DIRECTORY_SEPARATOR, array(
						$root_directory,
						!($extension = pathinfo($target, PATHINFO_EXTENSION))
							? $target . '.php'
							: $target
					));
				} else {
					$output_file = NULL;
				}


				if (
					preg_match(iw::REGEX_VARIABLE, $builder)
					&& class_exists($builder)
				) {

					$make_method = iw::makeTarget(
						$builder,
						self::DYNAMIC_SCAFFOLD_METHOD
					);

					if (is_callable($make_method)) {
						$make_target = pathinfo($target, PATHINFO_FILENAME);
						call_user_func($make_method, $make_target);
					}

					$build_method = iw::makeTarget(
						$builder,
						self::FINAL_SCAFFOLD_METHOD
					);

					if (is_callable($build_method)) {
						call_user_func(
							$build_method,
							$target,
							$output_file,
							self::$lastScaffoldedCode
						);
					} else {
						$file = fFile::create(
							$output_file,
							self::$lastScaffoldedCode
						);
					}

					self::$lastScaffoldedCode = NULL;

				} else {
					throw new fException(
						'Non-class scaffolding not supported by build.'
					);
				}

				self::$isBuilding = FALSE;
			} catch (Exception $e) {
				self::$isBuilding = FALSE;
				throw $e;
			}
		}

		/**
		 * Runs the scaffolder with a particular template.  This method will
		 * generally be called from a class's __make() method
		 *
		 * @static
		 * @access public
		 * @param string $template The template to use for scaffolding
		 * @param array $support_vars An associative array of variables to import into the template
		 * @param string $build_class The class from which scaffolding is running
		 * @param boolean $eval Whether or not the code should be evalulated.
		 * @return string The code
		 */
		static public function make($template, $template_vars = array(), $build_class = NULL, $eval = TRUE)
		{

			if (extract($template_vars, EXTR_SKIP) == sizeof($template_vars)) {

				$template = implode(DIRECTORY_SEPARATOR, array(
					self::$scaffoldingRoot,
					(pathinfo($template, PATHINFO_EXTENSION))
						? $template
						: $template . '.php'
				));

				if (!is_readable($template)) {
					throw new fProgrammerException(
						'Scaffolder cannot make %s, template %s not found',
						$class,
						$template
					);
				} else {
					ob_start();
					include $template;
					$code = ob_get_clean();

					if ($eval) {
						ob_start();
						eval($code);
						ob_end_clean();
					}

					$code = '<?php' . "\n\n" . $code;

					return (self::$isBuilding)
						? (self::$lastScaffoldedCode = $code)
						: $code;
				}

			} else {
				throw new fProgrammerException(
					'Cannot scaffold, invalid template variable names'
				);
			}
		}

		/**
		 * Exports variables in the same sense as var_export(), however does
		 * some cleanup for arrays and other types.
		 *
		 * @static
		 * @access private
		 * @param mixed $variable The variable to export
		 * @return string A PHP parseable version of the variable
		 */
		static private function exportVariable($variable)
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

		/**
		 * Validates a string as a variable/class name.
		 *
		 * @static
		 * @access private
		 * @param string $variable
		 * @return string The class name for inclusion if it is valid
		 * @throws fValidationException In the event the variable name is unsafe
		 */
		static private function validateVariable($variable)
		{
			if (preg_match(iw::REGEX_VARIABLE, $variable)) {
				return $variable;
			} else {
				throw new fValidationException(
					'Scaffolder template detected an invalid variable named %s',
					$variable
				);
			}
		}
	}
