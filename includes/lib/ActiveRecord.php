<?php

	/**
	 * ActiveRecord is an abstract base class for all flourish active records.
	 * It provides a series of common methods used by common Controller objects.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class ActiveRecord extends fActiveRecord implements inkwell
	{

		const DEFAULT_FIELD_SEPARATOR = '-';
		const DEFAULT_WORD_SEPARATOR  = '_';

		static private $info                 = array();

		static private $nameTranslations     = array();
		static private $tableTranslations    = array();
		static private $setTranslations      = array();
		static private $entryTranslations    = array();

		static private $fieldSeparator       = NULL;
		static private $wordSeparator        = NULL;

		static private $imageUploadDirectory = NULL;
		static private $fileUploadDirectory  = NULL;

		/**
		 * Represents the object as a string
		 *
		 * @return string The string representation of the object
		 */
		public function __toString()
		{
			$record_class = get_class($this);

			if (($id_column = self::getInfo($record_class, 'id_column'))) {
				return self::encode($id_column);
			}

			return fGrammar::humanize($record_class);
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

			foreach ($pkey_methods as $pkey_method) {
				$pkey_data[] = fURL::makeFriendly($this->$pkey_method());
			}

			$slug = implode(self::$fieldSeparator, $pkey_data);

			if ($identify) {
				$slug = implode('/', array(
					fURL::makeFriendly($this->__toString()),
					$slug
				));
			}

			return $slug;
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
			return implode('-', array(
				self::getEntry(get_class($this)),
				$this->makeSlug(FALSE)
			));
		}

		/**
		 * Matches whether or not a given class name is a potential
		 * ActiveRecord by looking for the tablized form in the list of
		 * database tables.
		 *
		 * @param string $class The name of the class to check
		 * @return boolean TRUE if it matches, FALSE otherwise
		 */
		static public function __match($class) {
			$schema = fORMSchema::retrieve();
			return in_array(fORM::tablize($class), $schema->getTables());
		}

		/**
		 * Initializses the ActiveRecord class
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config, $record_class = NULL)
		{
			if (!$record_class) {

				self::$imageUploadDirectory = iw::getWriteDirectory('images');
				self::$fileUploadDirectory  = iw::getWriteDirectory('files');

				self::$fieldSeparator = (isset($config['field_separator']))
					? $config['field_separator']
					: self::DEFAULT_FIELD_SEPARATOR;

				self::$wordSeparator = (isset($config['word_separator']))
					? $config['word_separator']
					: self::DEFAULT_WORD_SEPARATOR;

				// Configure active records

				$ar_configs = iw::getConfigsByType('ActiveRecord');

				foreach ($ar_configs as $config_element => $config) {

					$record_class = fGrammar::camelize($config_element, TRUE);
					$database     = NULL;
					$table        = NULL;
					$name         = NULL;
					$entry        = NULL; // TODO: Should not be on ActiveRecord

					extract($config);

					if (isset($database)) {
						if ($database !== 'default') {
							fORM::mapClassToDatabase($record_class, $database);
						}
					}

					if (isset($table)) {
						self::$tableTranslations[$record_class] = $table;
						fORM::mapClassToTable($record_class, $table);
					}

					if (isset($name)) {
						self::$nameTranslations[$record_class]  = $name;
					}

					// TODO: Entries and entry translations should not be a
					// TODO: function of the model by the controller... this
					// TODO: needs to be cleaned up.

					if (isset($entry)) {
						self::$entryTranslations[$record_class] = $entry;
					}

				}

				return TRUE;

			} elseif (!is_subclass_of($record_class, __CLASS__)) {
				return FALSE;
			}

			$schema = fORMSchema::retrieve($record_class);
			$table  = fORM::tablize($record_class);

			// Default and Configurable Values

			if (isset($config['id_column'])) {
				self::$info[$record_class]['id_column'] = $config['id_column'];
			} else {
				$u_keys = $schema->getKeys($table, 'unique');
				if (sizeof($u_keys) == 1 && sizeof($u_keys[0]) == 1) {
					self::$info[$record_class]['id_column'] = $u_keys[0][0];
				} else {
					self::$info[$record_class]['id_column'] = NULL;
				}
			}

			if (isset($config['order'])) {
				if (!is_array($config['order'])) {
					throw new fProgrammerException (
						'Order configuration is expected to be an array.'
					);
				}
				self::$info[$record_class]['order'] = $config['order'];
			} else {
				self::$info[$record_class]['order'] = array();
			}

			$column_configs = array(
				'image_columns',
				'file_columns',
				'password_columns',
				'order_columns',
				'money_columns',
				'url_columns',
				'email_columns',
				'fixed_columns'
			);

			foreach($column_configs as $column_config) {

				self::$info[$record_class][$column_config] = array();

				if (!isset($config[$column_config])) {
					continue;
				}

				// Make sure the user has configured an array

				if (!is_array($config[$column_config])) {
					throw new fProgrammerException (
						'%s must be configured as an array.',
						fGrammar::humanize($column_config)
					);
				}

				// If so, add each column respectively and run any
				// special configuration depending on the
				// $column_config

				foreach ($config[$column_config] as $column) {

					self::$info[$record_class][$column_config][] = $column;

					switch ($column_config) {

						// Special handling of image columns

						case 'image_columns':
							fORMFile::configureImageUploadColumn(
								$record_class,
								$column,
								iw::getWriteDirectory(
									implode(DIRECTORY_SEPARATOR, array(
										self::$imageUploadDirectory,
										fGrammar::pluralize($column)
									))
								)
							);
							break;

						// Special handling of file columns

						case 'file_columns':
							fORMFile::configureFileUploadColumn(
								$record_class,
								$column,
								iw::getWriteDirectory(
									implode(DIRECTORY_SEPARATOR, array(
										self::$fileUploadDirectory,
										fGrammar::pluralize($column)
									))
								)
							);
							break;

						// Special handling for order columns

						case 'order_columns':
							fORMOrdering::configureOrderingColumn(
								$record_class,
								$column
							);

							$order =& self::$info[$record_class]['order'];
							if (!isset($order[$column])) {
								$order[$column] = 'asc';
							}

							break;

						// Special handling for URL columns

						case 'url_columns':
							fORMColumn::configureLinkColumn(
								$record_class,
								$column
							);
							break;

						// Special handling for e-mail columns

						case 'email_columns':
							fORMColumn::configureEmailColumn(
								$record_class,
								$column
							);
							break;

						// Special handling for money columns

						case 'money_columns':
							fORMMoney::configureMoneyColumn(
								$record_class,
								$column
							);
							break;
					}
				}
			}

			// If there are password columns, handle them properly

			if (count(self::$info[$record_class]['password_columns'])) {
				fORM::registerHookCallback(
					$record_class,
					'pre::validate()',
					iw::makeTarget(__CLASS__, 'handlePasswordColumns')
				);
			}

			// Set all non-configurable information

			self::$info[$record_class]['columns']        = array();
			self::$info[$record_class]['pkey_columns']   = array();
			self::$info[$record_class]['pkey_methods']   = array();
			self::$info[$record_class]['fkey_columns']   = array();
			self::$info[$record_class]['serial_columns'] = array();
			self::$info[$record_class]['fixed_columns']  = array();

			foreach ($schema->getColumnInfo($table) as $column => $info) {

				self::$info[$record_class]['columns'][] = $column;

				fORM::registerInspectCallback(
					$record_class,
					$column,
					iw::makeTarget(__CLASS__, 'inspectColumn')
				);

				if ($info['auto_increment']) {
					self::$info[$record_class]['serial_columns'][] = $column;
				}
			}

			foreach ($schema->getKeys($table, 'primary') as $column) {

				$method = 'get' . fGrammar::camelize($column, TRUE);
				self::$info[$record_class]['pkey_columns'][] = $column;
				self::$info[$record_class]['pkey_methods'][] = $method;
			}

			foreach ($schema->getKeys($table, 'foreign') as $fkey_info) {

				$column = $fkey_info['column'];
				self::$info[$record_class]['fkey_columns'][] = $column;
			}

			self::$info[$record_class]['is_relationship'] = !count(
				array_diff(
					self::$info[$record_class]['columns'],
					self::$info[$record_class]['pkey_columns']
				)
			);

			return TRUE;
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

			$tables = fORMSchema::retrieve($record_class)->getTables();
			$table  = fORM::tablize($record_class);

			if (in_array($table, $tables)) {

				Scaffolder::makeClass($record_class, __CLASS__, array());

				if (class_exists($record_class, FALSE)) {
					return TRUE;
				}
			}
			return FALSE;
		}

		/**
		 * Inspects a column on a particular record class.  If this is called
		 * using the inspectColumn() method on an active record it will add
		 * enhanced information.
		 *
		 * @param string $record_class The Active Record class
		 * @param string $column The name of the column
		 * @param array $info The array of current inspection information
		 * @return array The enhanced inspection information
		 */
		static public function inspectColumn($record_class, $column, &$info = array())
		{

			$schema = fORMSchema::retrieve($record_class);
			$table  = self::getRecordTable($record_class);

			// Populate basic information if it is not provided

			if (!count($info)) {
				$info = $schema->getColumnInfo($record_class, $column);
			}

			// Populate advanced foreign key information

			$fkey_info       = array();
			$info['is_fkey'] = FALSE;

			foreach ($schema->getKeys($table, 'foreign') as $fkey) {
				if ($fkey['column'] == $column) {

					$info['is_fkey'] = TRUE;
					$info            = array_merge($info, $fkey);
				}
			}

			// Determine any special formatting for the column

			$image_columns = self::getInfo($record_class, 'image_columns');
			$file_columns  = self::getInfo($record_class, 'file_columns');
			$pass_columns  = self::getInfo($record_class, 'password_columns');
			$order_columns = self::getInfo($record_class, 'order_columns');
			$url_columns   = self::getInfo($record_class, 'url_columns');
			$email_columns = self::getInfo($record_class, 'email_columns');

			if (in_array($column, $order_columns)) {
				$info['format'] = 'ordering';

			} elseif (in_array($column, $image_columns)) {
				$info['format'] = 'image';

			} elseif (in_array($column, $file_columns)) {
				$info['format'] = 'file';

			} elseif (in_array($column, $pass_columns)) {
				$info['format'] = 'password';

			} elseif (in_array($column, $url_columns)) {
				$info['format'] = 'url';

			} elseif (in_array($column, $email_columns)) {
				$info['format'] = 'email';

			} elseif ($info['is_fkey']) {

				$relationships = $schema->getRelationships($table);

				foreach ($relationships as $type => $relationship) {
					foreach ($relationship as $relation_info) {
						if ($relation_info['column'] == $column) {
							switch ($type) {
								case 'one-to-many':
									$info['format'] = 'records';
									break;
								case 'one-to-one':
								case 'many-to-one':
									$info['format'] = 'record';
									break;
							}
						}
					}
				}

			}

			// Last ditch attempt to get a usable format

			if (!isset($info['format'])) {

				switch ($info['type']) {
					case 'varchar':
						$info['format'] = 'string';
						break;
					case 'boolean':
						$info['format'] = 'checkbox';
						break;
					default:
						$info['format'] = $info['type'];
						break;
				}
			}

			// Determine additional properties

			$fixed_columns     = self::getInfo($record_class, 'fixed_columns');
			$serial_columns    = self::getInfo($record_class, 'serial_columns');

			$info['serial']    = FALSE;

			if (in_array($column, $fixed_columns) ||
				$info['format'] == 'ordering'
			) {
				$info['fixed']  = TRUE;
			} elseif (in_array($column, $serial_columns)) {
				$info['fixed']  = TRUE;
				$info['serial'] = TRUE;
			} else {
				$info['fixed']  = FALSE;
			}

			return $info;
		}

		/**
		 * Converts a record name into a class name, for example: user to
		 * User or user_photograph to UserPhotograph
		 *
		 * @param string $record The name of the record
		 * @return string|NULL The class name of the record or NULL if it does not exist
		 */
		static public function classFromRecordName($record_name)
		{
			if (!in_array($record_name, self::$recordTranslations)) {
				self::$nameTranslations[$record] = NULL;
				try {
					$record_class = fGrammar::camelize($record_name, TRUE);
					if (class_exists($record_class) &&
						is_subclass_of($record_class, __CLASS__)
					) {
						self::$recordTranslations[$record_class] = $record_name;
					}
				} catch (fProgrammerException $e) {}
			}

			return array_search($record, self::$nameTranslations);
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
					if (class_exists($record_class) &&
						is_subclass_of($record_class, __CLASS__)
					) {
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
					// The class_exists() is a workaround for PHP bug #46753
					// it should not be required as is_subclass_of should
					// properly trigger autoload.  This behavior is fixed
					// in PHP 5.3+
					if (class_exists($record_class) &&
						is_subclass_of($record_class, __CLASS__)
					) {
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
					$record_class = fGrammar::camelize($singularized, TRUE);
					if (class_exists($record_class) &&
						is_subclass_of($record_class, __CLASS__)
					) {
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
		static public function getRecordName($record_class)
		{
			if (isset(self::$nameTranslations[$record_class])) {
				return self::$nameTranslations[$record_class];
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
		 * Gets the the ordering of the Active Record class
		 *
		 * @param string $record_class The Active Record class name
		 * @return array The ordering array for the Active Record class
		 */
		static public function getOrder($record_class)
		{
			return self::getInfo($record_class, 'order');
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
		 * A validation hook for password columns. If any columns are set as
		 * password columns, this method will be registered to ensure that a
		 * password confirmation field matches the original field when storing
		 * the record or that if a password is already set, an empty value will
		 * result in no change.
		 *
		 * @param fActiveRecord The active record object
		 * @param array $values The new column values being set
		 * @param array $old_values The original column values
		 * @param array $related The related records array for the record
		 * @param array $cache The cache array for the record
		 * @param array $validation_messages An array of validation messages
		 * @return void
		 */
		static public function handlePasswordColumns($object, &$values, &$old_values, &$related_records, &$cache, &$validation_messages)
		{
			$record_class     = get_class($object);
			$password_columns = self::getInfo($record_class, 'password_columns');

			foreach ($password_columns as $password_column) {

				if (
					!empty($values[$password_column])     &&
					!empty($old_values[$password_column])
				) {

					$confirmation = fRequest::get(implode('-', array(
						'confirm',
						$password_column
					)));

					if ($confirmation == $values[$password_column]) {

						$values[$password_column] = fCryptography::hashPassword(
							$values[$password_column]
						);

					} else {
						$validation_messages[] = fText::compose(
							'post',
							'%s: Does not match confirmation field',
							fGrammar::humanize($password_column)
						);
					}

				} elseif (!empty($old_values[$password_column])) {

					$values[$password_column] = end(
						$old_values[$password_column]
					);

				}
			}
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
				$pkey_data    = explode(self::$fieldSeparator, $slug);

				if (sizeof($pkey_data) < sizeof($pkey_columns)) {
					throw new fProgrammerException(
						'Malformed slug for class %s, check the primary key.',
						$record_class
					);
				}

				if (sizeof($pkey_columns) == 1) {
					$pkey = implode(self::$fieldSeparator, $pkey_data);
				} else {

					foreach ($pkey_columns as $pkey_column) {
						$pkey[$pkey_column] = array_shift($pkey_data);
						$last_column        = &$pkey[$pkey_column];
					}

					// Allows for fieldSeparator in final pkey column

					if (count($pkey_data) > 0) {
						$last_column .= implode(
							self::$fieldSeparator,
							$pkey_data
						);
					}
				}

				$record = new $record_class($pkey);

				if ($identifier !== NULL) {
					$match_id = fURL::makeFriendly($record->__toString());
					if ($identifier != $match_id) {
						throw new fValidationException(
							'Provided identifier does not match.'
						);
					}
				}

				return $record;
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

			return NULL;
		}

	}
