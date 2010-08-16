	/**
	 * The <%= $class %> is an active record and model representing a single
	 * <%= fGrammar::humanize($class) %> record.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class <%= $class %> extends <%= $parent_class %>

	{

		// Custom Object Methods

		/**
		 * Initializes all static class information for the <%= $class %> model
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
			parent::__init($config, __CLASS__);
		}

		/**
		 * Gets the record name for the <%= $class %> class
		 *
		 * @return string The custom or default record translation
		 */
		static public function getRecordName()
		{
			return parent::getRecordName(__CLASS__);
		}

		/**
		 * Gets the record table name for the <%= $class %> class
		 *
		 * @return string The custom or default record table translation
		 */
		static public function getRecordTable()
		{
			return parent::getRecordTable(__CLASS__);
		}

		/**
		 * Gets the record set name for the <%= $class %> class
		 *
		 * @return string The custom or default record set translation
		 */
		static public function getRecordSet()
		{
			return parent::getRecordSet(__CLASS__);
		}

		/**
		 * Gets the entry name for the <%= $class %> class
		 *
		 * @return string The custom or default entry translation
		 */
		static public function getEntry()
		{
			return parent::getEntry(__CLASS__);
		}

		/**
		 * Gets the order for the <%= $class %> class
		 *
		 * @return array The default sort array
		 */
		static public function getOrder()
		{
			return parent::getOrder(__CLASS__);
		}

		/**
		 * Determines whether the record class only serves as a relationship,
		 * i.e. a many to many table.
		 *
		 * @return boolean TRUE if it is a relationship, FALSE otherwise
		 */
		static public function isRelationship()
		{
			return parent::isRelationship(__CLASS__);
		}

		/**
		 * Creates a new <%= $class %> from a slug and identifier.  The
		 * identifier is optional, but if is provided acts as an additional
		 * check against the validity of the record.
		 *
		 * @param $slug A primary key string representation or "slug" string
		 * @param $identifier The identifier as provided as an extension to the slug
		 * @return fActiveRecord The active record matching the slug
		 */
		static public function createFromSlug($slug, $identifier = NULL)
		{
			return parent::createFromSlug(__CLASS__, $slug, $identifier);
		}

<% if (!$scaffolding) { %>
		/**
		 * Allows for a dynamically created active record to be scaffolded.
		 *
		 * @param string $file
		 * @return void
		 */
		static public function __scaffold($file = NULL) {

			if (!$file) {
				$file = implode(DIRECTORY_SEPARATOR, array(
					$_SERVER['DOCUMENT_ROOT'], // document_root
					'models',                  // path
					__CLASS__ . '.php'         // file
				));
			}

			Scaffolder::writeClass($file, __CLASS__, '<%= $parent_class %>');

		}
<% } %>

		// Custom Class Methods

	}
