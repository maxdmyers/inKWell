	/**
	 * The <?= $class ?> is an active record and model representing a single
	 * <?= fGrammar::humanize($class) ?> record.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class <?= $class ?> extends <?= $parent_class ?>

	{

		// Custom Object Methods

		/**
		 * Initializes all static class information for <?= $class ?> model
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
			parent::__init($config, __CLASS__);
		}

		/**
		 * Sets information for the <?= $class ?> model.
		 *
		 * @param mixed $values An associative array of information to set.
		 * @return void
		 */
		static public function setInfo($values)
		{
			return parent::setInfo(__CLASS__, $values);
		}

		/**
		 * Gets the record name for the <?= $class ?> class
		 *
		 * @return string The custom or default record translation
		 */
		static public function getRecord()
		{
			return parent::getRecord(__CLASS__);
		}

		/**
		 * Gets the record table name for the <?= $class ?> class
		 *
		 * @return string The custom or default record table translation
		 */
		static public function getRecordTable()
		{
			return parent::getRecordTable(__CLASS__);
		}

		/**
		 * Gets the record set name for the <?= $class ?> class
		 *
		 * @return string The custom or default record set translation
		 */
		static public function getRecordSet()
		{
			return parent::getRecordSet(__CLASS__);
		}

		/**
		 * Gets the entry name for the <?= $class ?> class
		 *
		 * @return string The custom or default entry translation
		 */
		static public function getEntry()
		{
			return parent::getEntry(__CLASS__);
		}

		/**
		 * Gets the the default sorting for the <?= $class ?> class
		 *
		 * @return array The default sort array
		 */
		static public function getDefaultSorting()
		{
			return parent::getDefaultSorting(__CLASS__);
		}

		/**
		 * Determines whether or not a column name represents a foreign key
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a foreign key column, FALSE otherwise
		 */
		static public function isFKeyColumn($column)
		{
			return parent::isFKeyColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents an image upload
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is an image upload column, FALSE otherwise
		 */
		static public function isImageColumn($column)
		{
			return parent::isImageColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents a file upload
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a file upload column, FALSE otherwise
		 */
		static public function isFileColumn($column)
		{
			return parent::isFileColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents a password
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a password column, FALSE otherwise
		 */
		static public function isPasswordColumn($column)
		{
			return parent::isPasswordColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents a read-only
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a read-only column, FALSE otherwise
		 */
		static public function isReadOnlyColumn($column)
		{
			return parent::isReadOnlyColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not a column name represents an auto-increment
		 * column
		 *
		 * @param string $column The column to check
		 * @return boolean TRUE if it is an auto-increment column, FALSE otherwise
		 */
		static public function isAIColumn($column)
		{
			return parent::isAIColumn(__CLASS__, $column);
		}

		/**
		 * Determines whether or not the record is allowed to be mapped to
		 * dynamically from entry points or controllers.
		 *
		 * @return boolean TRUE if the record class can be mapped, FALSE otherwise.
		 */
		static public function canMap()
		{
			return parent::canMap(__CLASS__);
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
		 * Creates a new <?= $class ?> from a slug and identifier.  The
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

<? if (!$scaffolding) { ?>
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

			Scaffolder::writeClass($file, __CLASS__, '<?= $parent_class ?>');

		}
<? } ?>

		// Custom Class Methods

	}
