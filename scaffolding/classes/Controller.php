	/**
	 * The <%= $class %>, a standard controller class.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 */
	class <%= self::validateVariable($class) %> extends <%= self::validateVariable($build_class) %>

	{

		/**
		 * Prepares a new <%= $class %> for running actions.
		 *
		 * @access protected
		 * @param string void
		 * @return void
		 */
		protected function prepare()
		{
			// The controller prepare method should be called only if you
			// are building out full pages or responses, not for controllers
			// which only provide embeddable views.
			//
			// return parent::prepare(__CLASS__);
		}

		/**
		 * Initializes all static class information for the <%= $class %> class
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return boolean TRUE if the initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array(), $element = NULL)
		{
			// All custom initialization goes here, make sure to check any
			// configuration you're setting up for errors and return FALSE
			// in the event the class cannot be initialized with the provided
			// configuration.

			return TRUE;
		}

	}
