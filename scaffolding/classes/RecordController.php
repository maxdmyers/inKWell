
	/**
	 * The <%= $class %> is an active record controller responsible for
	 * providing the needed entry point handlers, view rendering, etc for
	 * the supported actions on the <%= $class %> class.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class <%= $class %> extends <%= $build_class %>

	{

		/**
		 * Prepares a new <%= $class %> for running actions.
		 *
		 * @param string void
		 * @return void
		 */
		protected function prepare()
		{
			return parent::prepare(__CLASS__);
		}

		/**
		 * Initializes all static class information for the <%= $class %> class
		 *
		 * @param array $config The configuration array
		 * @return boolean TRUE if initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array())
		{
			return parent::__init($config, __CLASS__);
		}

		/**
		 * Determines the appropriate Active Record for which the
		 * <%= $class %> is responsible.
		 *
		 * @param void
		 * @return string The name of the Active Record class
		 */
		static protected function getRecordClass()
		{
			return parent::getRecordClass(__CLASS__);
		}

<% foreach($supported_actions as $action) { %>
		/**
		 * Entry handler for the <%= $action %> action.
		 *
		 * @param void
		 * @return void
		 */
		static public function <%= $action %>()
		{
			return parent::<%= $action %>(__CLASS__);
		}

<% } %>

	}
