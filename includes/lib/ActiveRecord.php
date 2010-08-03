<?php

	/**
	 * ActiveRecord is an abstract base class for all flourish active records.
	 * It provides a series of common methods used by common Controller objects.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class ActiveRecord extends fActiveRecord
	{
		static private $info               = array();
		static private $setTranslations    = array();
		static private $recordTranslations = array();
		static private $tableTranslations  = array();
		static private $entryTranslations  = array();

		/**
		 * Represents the object as a string
		 *
		 * @return string The string representation of the object
		 */
		public function __toString()
		{
			return fGrammar::underscorize(get_class($this));
		}

		/**
		 * Populates a record using fRequest get.  The format of the name
		 * value should be such of $record[$column].
		 *
		 * @param void
		 * @rretur ActiveRecord The active record for method chaining
		 */
		 public function populate()
		 {
		 	$record_class = get_class($this);
		 	$record       = self::getInfo($record_class, 'record');
			$columns      = self::getInfo($record_class, 'columns');

			fORM::callHookCallbacks(
				$this,
				'pre::populate()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);

			$data = fRequest::get($record, 'array', array());
			foreach ($columns as $column) {
				if (isset($data[$column])) {
					$method = 'set' . fGrammar::camelize($column, TRUE);
					$this->$method($data[$column]);
				}
			}

			fORM::callHookCallbacks(
				$this,
				'post::populate()',
				$this->values,
				$this->old_values,
				$this->related_records,
				$this->cache
			);

			return $this;
		 }

		/**
		 * Creates an identifying slug which can be comprised ultimately of the
		 * record name, a string representation of the primary key, and
		 * optionally the return value of the identifying method.
		 *
		 * @param string $prepend_record Whether or not to prepend the record name to the slug
		 * @return string The slug representation of the active record.
		 */
		public function makeSlug($identify = TRUE)
		{

			$record_class = get_class($this);
			$pkey_methods = self::getInfo($record_class, 'pkey_methods');
			$identifier   = fURL::makeFriendly($this->__toString());

			foreach ($pkey_methods as $pkey_method) {
				$pkey_data[] = fURL::makeFriendly($this->$pkey_method());
			}

			return implode('-', $pkey_data) . (($identify) ? '/' . $identifier : '');
		}

		/**
		 * Creates a resource key which is comprised ultimately of the entry
		 * name and a string representation of the primary key.
		 *
		 * @param void
		 * @return void
		 */
		public function makeResourceKey()
		{
			return self::getInfo(get_class($this), 'entry') . '-' . $this->makeSlug(FALSE);
		}

		/**
		 * Initializses the ActiveRecord class
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		 static public function __init($config)
		 {
		 }

		/**
		 * Converts a record name into a class name, for example: user to
		 * User or user_photograph to UserPhotograph
		 *
		 * @param string $record The name of the record
		 * @return string|NULL The class name of the record or NULL if it does not exist
		 */
		static public function classFromRecord($record)
		{
			if (!in_array($record, self::$recordTranslations)) {
				self::$recordTranslations[$record] = NULL;
				try {
					$record_class = fGrammar::camelize($record, TRUE);
					if (@is_subclass_of($record_class, __CLASS__)) {
						self::$recordTranslations[$record_class] = $record;
					}
				} catch (fProgrammerException $e) {}
			}

			return array_search($record, self::$recordTranslations);
		}

		/**
		 * Converts a table name into an active record class name, for example:
		 * users to User
		 *
		 * @param string $table The name of the table
		 * @return string|NULL The class name of the record or NULL if it does not exist
		 */
		static public function classFromRecordTable($record_table)
		{
			if (!in_array($record_table, self::$tableTranslations)) {
				try {
					$record_class = fORM::classize($record_table);
					if (@is_subclass_of($record_class, __CLASS__)) {
						self::$tableTranslations[$record_class] = $record_table;
					}
				} catch (fProgrammerException $e) {}
			}

			return array_search($record_table, self::$tableTranslations);
		}

		/**
		 * Converts a record set class name into an active record class name,
		 * for example: Users to User
		 *
		 * @param string $recordset The name of the recordset
		 * @return string|NULL The class name of the active record or NULL if it does not exist
		 */
		static public function classFromRecordSet($record_set)
		{
			if (!in_array($record_set, self::$setTranslations)) {
				try {
					$record_class = fGrammar::singularize($record_set);
					if (@is_subclass_of($record_class, __CLASS__)) {
						self::$setTranslations[$record_class] = $record_set;
					}
				} catch (fProgrammerException $e) {}
			}

			return array_search($record_set, self::$setTranslations);
		}

		/**
		 * Converts an entry name into an active record class name, for example:
		 * users to User or user_photographs to UserPhotograph
		 *
		 * @param string $entry The entry name to convert
		 * @return string|NULL The class name of the active record or NULL if it does not exist
		 */
		static public function classFromEntry($entry)
		{
			if (!in_array($entry, self::$entryTranslations)) {
				try {
					$singularized = fGrammar::singularize($entry);
					$record_class = fGrammar::camelize($entry, TRUE);
					if (@is_subclass_of($record_class, __CLASS__)) {
						self::$entryTranslations[$record_class] = $entry;
					}
				} catch (fProgrammerException $e) {}
			}

			return array_search($entry, self::$entryTranslations);
		}

		/**
		 * Gets the record name for an Active Record class
		 *
		 * @param string $record_class The Active Record class name
		 * @return string The custom or default record translation
		 */
		static public function getRecord($record_class)
		{
			if (isset(self::$recordTranslations[$record_class])) {
				return self::$recordTranslations[$record_class];
			} else {
				return fGrammar::underscorize($record_class);
			}
		}

		/**
		 * Gets the record table name for an Active Record class
		 *
		 * @param string $record_class The Active Record class name
		 * @return string The custom or default record table translation
		 */
		static public function getRecordTable($record_class)
		{
			if (isset(self::$tableTranslations[$record_class])) {
				return self::$tableTranslations[$record_class];
			} else {
				return fORM::tablize($record_class);
			}
		}

		/**
		 * Gets the record set name for an Active Record class
		 *
		 * @param string $record_class The Active Record class name
		 * @return string The custom or default record set translation
		 */
		static public function getRecordSet($record_class)
		{
			if (isset(self::$setTranslations[$record_class])) {
				return self::$setTranslations[$record_class];
			} else {
				return fGrammar::pluralize($record_class);;
			}
		}

		/**
		 * Gets the entry name for an Active Record class
		 *
		 * @param string $record_class The Active Record class name
		 * @return string The custom or default entry translation
		 */
		static public function getEntry($record_class)
		{
			if (isset(self::$entryTranslations[$record_class])) {
				return self::$entryTranslations[$record_class];
			} else {
				$record_set = self::getRecordSet($record_class);
				return fGrammar::underscorize($record_set);
			}
		}

		/**
		 * Gets the the default sorting for the Active Record class
		 *
		 * @param string $record_class The Active Record class name
		 * @return array The default sort array
		 */
		static public function getDefaultSorting($record_class)
		{
			return self::getInfo($record_class, 'default_sorting');
		}

		/**
		 * Determines whether or not a column name represents a foreign key
		 * column
		 *
		 * @param string $record_class The name of the active record class
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a foreign key column, FALSE otherwise
		 */
		static public function isFKeyColumn($record_class, $column)
		{
			$fkey_info    = self::getInfo($record_class, 'fkey_info');
			$fkey_columns = array_keys($fkey_info);
			return in_array($column, $fkey_columns);
		}

		/**
		 * Determines whether or not a column name represents an image upload
		 * column
		 *
		 * @param string $record_class The name of the active record class
		 * @param string $column The column to check
		 * @return boolean TRUE if it is an image upload column, FALSE otherwise
		 */
		static public function isImageColumn($record_class, $column)
		{
			$image_columns = self::getInfo($record_class, 'image_columns');
			return in_array($column, $image_columns);
		}

		/**
		 * Determines whether or not a column name represents a file upload
		 * column
		 *
		 * @param string $record_class The name of the active record class
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a file upload column, FALSE otherwise
		 */
		static public function isFileColumn($record_class, $column)
		{
			$file_columns = self::getInfo($record_class, 'file_columns');
			return in_array($column, $file_columns);
		}

		/**
		 * Determines whether or not a column name represents a password
		 * column
		 *
		 * @param string $record_class The name of the active record class
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a password column, FALSE otherwise
		 */
		static public function isPasswordColumn($record_class, $column)
		{
			$password_columns = self::getInfo($record_class, 'password_columns');
			return in_array($column, $password_columns);
		}

		/**
		 * Determines whether or not a column name represents a read-only
		 * column
		 *
		 * @param string $record_class The name of the active record class
		 * @param string $column The column to check
		 * @return boolean TRUE if it is a read-only column, FALSE otherwise
		 */
		static public function isReadOnlyColumn($record_class, $column)
		{
			$read_only_columns = self::getInfo($record_class, 'read_only_columns');
			return (
				in_array($column, $read_only_columns)    ||
				self::isAIColumn($record_class, $column)
			);
		}

		/**
		 * Determines whether or not a column name represents an auto-increment
		 * column
		 *
		 * @param string $record_class The name of the active record class
		 * @param string $column The column to check
		 * @return boolean TRUE if it is an auto-increment column, FALSE otherwise
		 */
		static public function isAIColumn($record_class, $column)
		{
			$ai_columns = self::getInfo($record_class, 'ai_columns');
			return in_array($column, $ai_columns);
		}

		/**
		 * Determines whether or not the record is allowed to be mapped to
		 * dynamically from entry points or controllers.
		 *
		 * @param string $record_class The name of the active record class.
		 * @return boolean TRUE if the record class can be mapped, FALSE otherwise.
		 */
		static public function canMap($record_class)
		{
			return self::getInfo($record_class, 'allow_mapping');
		}

		/**
		 * Determines whether the class only serves as a relationship, i.e.
		 * a record in a many to many table.
		 *
		 * @param string $record_class The name of the active record class
		 * @return boolean TRUE if it is a relationship, FALSE otherwise
		 */
		static public function isRelationship($record_class)
		{
			return self::getInfo($record_class, 'is_relationship');
		}

		/**
		 * Dynamically defines an ActiveRecord if the provided class is the
		 * classized version of a table in the attached schema.
		 *
		 * @param string $record_class The Class name to dynamically define
		 * @return boolean TRUE if an active record was dynamically defined, FALSE otherwise
		 */
		static public function __make($record_class)
		{

			$tables = fORMSchema::retrieve()->getTables();
			if (in_array($table = fORM::tablize($record_class), $tables)) {

				eval(Scaffolder::makeClass($record_class, __CLASS__, array()));

				if (class_exists($record_class, FALSE)) {
					return TRUE;
				}
			}
			return FALSE;
		}

		/**
		 * Creates a record from a provided class, slug, and identifier.  The
		 * identifier is optional, but if is provided acts as an additional
		 * check against the validity of the record.
		 *
		 * @param $record_class The Active Record class
		 * @param $slug A primary key string representation or "slug" string
		 * @param $identifier The identifier as provided as an extension to the slug
		 * @return fActiveRecord The active record matching the slug
		 */
		static public function createFromSlug($record_class, $slug, $identifier = NULL)
		{
			if (empty($slug)) {
				return new $record_class();
			} else {

				$pkey_columns = self::getInfo($record_class, 'pkey_columns');
				$pkey_data    = explode('-', $slug);

				if (sizeof($pkey_data) < sizeof($pkey_columns)) {
					throw new fProgrammerException(
						'Cannot parse slug for class %s, slug is malformed', $record_class
					);
				}

				if (sizeof($pkey_columns) == 1) {
					$pkey = implode('-', $pkey_data);
				} else {
					foreach ($pkey_columns as $pkey_column) {
						$pkey[$pkey_column] = array_shift($pkey_data);
						$last_column        = &$pkey[$pkey_column];
					}

					// Allows for dashes in final pkey column

					if (count($pkey_data) > 0) {
						$last_column .= '-' . implode('-', $pkey_data);
					}
				}

				$record = new $record_class($pkey);

				if ($identifier !== NULL) {
					$match_identifier = fURL::makeFriendly($record->__toString());
					if ($identifier != $match_identifier) {
						throw new fValidationException(
							'The record identifier does not match.'
						);
					}
				}

				return $record;
			}
		}

		/**
		 * Initializes the class for use with ActiveRecord static methods.
		 *
		 * @param string $record_class The Active Record class name
		 * @return void
		 */
		static protected function register($record_class)
		{
			// Default Values

			$schema            = fORMSchema::retrieve();

			$record            = self::getRecord($record_class);
			$record_table      = self::getRecordTable($record_class);
			$record_set        = self::getRecordSet($record_class);
			$entry             = self::getEntry($record_class);

			$columns           = array_keys($schema->getColumnInfo($record_table));
			$keys              = $schema->getKeys($record_table);
			$pkey_columns      = $keys['primary'];
			$pkey_methods      = array();
			$fkey_info         = $keys['foreign'];

			$image_columns     = array();
			$file_columns      = array();
			$password_columns  = array();
			$read_only_columns = array();
			$ai_columns        = array();

			$default_sorting   = array();
			$is_relationship   = FALSE;

			foreach ($pkey_columns as $pkey_column) {
				$pkey_methods[] = 'get' . fGrammar::camelize($pkey_column, TRUE);
			}

			if (!count(array_diff($columns, $pkey_columns))) {
				$is_relationship = TRUE;
			}

			if (strpos($record_table, '.') === FALSE && !$is_relationship) {
				$allow_mapping = TRUE;
			} else {
				$allow_mapping = FALSE;
			}

			foreach ($schema->getColumnInfo($record_table) as $column => $info) {
				if ($info['auto_increment']) {
					$read_only_columns[] = $column;
					$ai_columns[]        = $column;
				}
			}

			self::setInfo($record_class, array(

				'record'            => $record,
				'record_table'      => $record_table,
				'record_set'        => $record_set,
				'entry'             => $entry,

				'columns'           => $columns,
				'pkey_columns'      => $pkey_columns,
				'pkey_methods'      => $pkey_methods,
				'fkey_info'         => $fkey_info,

				'image_columns'     => $image_columns,
				'file_columns'      => $file_columns,
				'password_columns'  => $password_columns,
				'read_only_columns' => $read_only_columns,
				'ai_columns'        => $ai_columns,

				'default_sorting'   => $default_sorting,
				'is_relationship'   => $is_relationship,
				'allow_mapping'     => $allow_mapping
			));

		}

		/**
		 * Sets information on a given Active Record class.
		 *
		 * @param string $record_class The Active Record class on which to set the information
		 * @param mixed $values An associative array of information to set.
		 * @return void
		 */
		static protected function setInfo($record_class = __CLASS__, array $values)
		{
			foreach ($values as $key => $value) {
				switch ($key) {

					case 'record':
						self::$recordTranslations[$record_class] = $value;
						break;

					case 'record_table':
						self::$tableTranslations[$record_class] = $value;
						fORM::mapClassToTable($record_class, $value);
						break;

					case 'record_set':
						self::$setTranslations[$record_class] = $value;
						break;

					case 'entry':
						self::$entryTranslations[$record_class] = $value;
						break;

					case 'image_columns':
						foreach ($value as $column) {
							$image_directory = iw::getWriteDirectory(
								implode(DIRECTORY_SEPARATOR, array(
									'images',
									fGrammar::pluralize($column)
								))
							);

							fORMFile::configureImageUploadColumn($record_class, $column, $image_directory);
						}
						break;

					case 'file_columns':
						foreach ($value as $column) {
							$file_directory = iw::getWriteDirectory(
								implode(DIRECTORY_SEPARATOR, array(
									'files',
									fGrammar::pluralize($column)
								))
							);

							fORMFile::configureFileUploadColumn($record_class, $column, $file_directory);
						}
						break;

					case 'password_columns':
					case 'read_only_columns':
					case 'ai_columns':
					case 'default_sorting':
					case 'allow_mapping':
						break;

					default:
						if (isset(self::$info[$record_class][$key])) {
							throw new fProgrammerException (
								'The key %s cannot be set manually.', $key
							);
						}
						break;
				}
				self::$info[$record_class][$key] = $value;
			}
		}


		/**
		 * Gets record information on a particular Active Record class.
		 *
		 * @param string $record_class The Active Record class name
		 * @param string $key Specific information rquested, NULL for all class info
		 * @return mixed The class information requested
		 */
		static private function getInfo($record_class, $key = NULL)
		{
			if (isset(self::$info[$record_class])) {
				if ($key !== NULL) {
					if (isset(self::$info[$record_class][$key])) {
						return self::$info[$record_class][$key];
					}
				} else {
					return self::$info[$record_class];
				}
			}

			throw new fProgrammerException(
				'Requested class information %s not set for %s, perhaps it has not been initialized yet.', $key, $record_class
			);
		}

	}
