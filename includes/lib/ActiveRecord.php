<?php

	/**
	 * ActiveRecord is an abstract base class for all flourish active records.
	 * It provides a series of common methods used by common Controller objects.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell
	 */
	abstract class ActiveRecord extends fActiveRecord implements inkwell, JSONSerializable
	{

		const DEFAULT_FIELD_SEPARATOR = '-';
		const DEFAULT_WORD_SEPARATOR  = '_';

		/**
		 * The cached slug
		 *
		 * @access private
		 * @var string
		 */
		private $slug = NULL;

		/**
		 * The cached resource key
		 *
		 * @access private
		 * @var string
		 */
		private $resourceKey = NULL;

		/**
		 * Cached information about the class built during __init()
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $info = array();

		/**
		 * Cached inspection information about table columns
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $inspectionInfo = array();

		/**
		 * Cached name translations for record classes
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $nameTranslations = array();

		/**
		 * Cached table translations for record classes
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $tableTranslations = array();

		/**
		 * Cached record set translations for record classes
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $setTranslations = array();

		/**
		 * Cached entry translations for record classes
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $entryTranslations = array();

		/**
		 * The slug field separator, default is a dash
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $fieldSeparator = NULL;

		/**
		 * The slug word separator, default is an underscore
		 *
		 * @static
		 * @access private
		 * @var string
		 */
		static private $wordSeparator = NULL;

		/**
		 * The base image upload directory
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $imageUploadDirectory = NULL;

		/**
		 * The base file upload directory
		 *
		 * @static
		 * @access private
		 * @var string|fDirectory
		 */
		static private $fileUploadDirectory = NULL;

		/**
		 * Represents the object as a string using the value of a configured
		 * or natural id_column.  If no such column exists, it uses the
		 * human version of the record class.
		 *
		 * @access public
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
		 * Default method for converting active record objects to JSON.  This
		 * will make all properties, normally private, publically available
		 * and return the object as a JSON encoded string.  As always, it can
		 * be overloaded.
		 *
		 * @access public
		 * @return string The JSON encoded object with public properties
		 */
		public function jsonSerialize()
		{
			$record_class = get_class($this);
			$schema       = fORMSchema::retrieve($record_class);
			$record_table = fORM::tablize($record_class);
			$columns      = array_keys($schema->getColumnInfo($record_table));
			$object       = new StdClass();

			foreach ($columns as $column) {
				$method          = 'get' . fGrammar::camelize($column, TRUE);
				$object->$column = $this->$method();
			}

			return $object;
		}

		/**
		 * Get the value of the record's primary key as passed to the
		 * constructor or as a serialized string.
		 *
		 * @access public
		 * @param boolean $serialize Whether or not to serialize the pkey
		 * @return mixed The primary key, usable in the constructor
		 */
		public function getPrimaryKey($serialize = FALSE)
		{
			$record_class = get_class($this);
			$columns      = self::getInfo($record_class, 'pkey_columns');
			$pkey         = array();

			foreach ($columns as $column) {
				$get_method    = 'get' . fGrammar::camelize($column, TRUE);
				$pkey[$column] = $this->$get_method();

				// Gets columns returned as objects in string form

				if (is_object($pkey[$column])) {
					$pkey[$column] = (string) $pkey[$column];
				}
			}

			if (count($pkey) == 1) {
				$pkey = reset($pkey);
			}

			return ($serialize) ? fJSON::encode($pkey) : $pkey;
		}

		/**
		 * Creates an identifying slug which can be comprised ultimately of a
		 * URL friendly string representation of the primary key and optionally
		 * the value of the record's configured id_column. The slug is HTML
		 * friendly by nature, although it is not independently HTML encoded.
		 *
		 * @access public
		 * @param string $identify Whether or not to append an identifier
		 * @return string The slug representation of the active record.
		 */
		public function makeSlug($identify = TRUE)
		{

			// The cached slug will be reset to NULL via the ::resetCache()
			// callback in the event any of the values comprising the slug
			// have changed.

			if (!$this->slug) {

				$record_class = get_class($this);
				$slug_column  = self::getInfo($record_class, 'slug_column');

				if ($slug_column) {
					$method = 'get' . fGrammar::camelize($slug_column, TRUE);
					$slug   = $this->$method();

				} else {

					if (!is_array($pkey = $this->getPrimaryKey())) {
						$pkey = array($pkey);
					}

					foreach ($pkey as $i => $value) {
						$pkey[$i] = fURL::makeFriendly(
							$value,
							NULL,
							self::$wordSeparator
						);
					}

					$slug = implode(self::$fieldSeparator, $pkey);

				}

				$this->slug = $slug;
			}

			if ($identify === TRUE) {
				return implode('/', array(
					$this->slug,
					fURL::makeFriendly(
						$this->__toString(),
						NULL,
						self::$wordSeparator
					)
				));
			}

			return $this->slug;
		}

		/**
		 * Creates a resource key which can be comprised ultimately of the
		 * JSON serialized primary key and optionally the identifier.  The
		 * returned value is not necessarily HTML safe and should be encoded
		 * if embedded in HTML.
		 *
		 * @access public
		 * @param boolean $identify Whether or not to append an identifiier
		 * @return string The JSON serialized resource key
		 */
		public function makeResourceKey($identify = TRUE)
		{

			// The cached resource key will be reset to NULL via the
			// ::resetCache() callback in the event any of the values
			// comprising the slug have changed.

			if (!$this->resourceKey) {

				$record_class = get_class($this);

				$resource_key = array(
					'primary_key' => $this->getPrimaryKey()
				);

				$this->resourceKey = $resource_key;
			}

			if ($identify === TRUE) {
				return fJSON::encode(array_merge(
					$this->resourceKey,
					array('identifier' => (string) $this)
				));
			}

			return fJSON::encode($this->resourceKey);
		}

		/**
		 * Matches whether or not a given class name is a potential
		 * ActiveRecord by looking for an available matching ActiveRecord
		 * configuration or the tablized form in the list of the default
		 * database tables.
		 *
		 * @static
		 * @access public
		 * @param string $class The name of the class to check
		 * @return boolean TRUE if it matches, FALSE otherwise
		 */
		static public function __match($class)
		{
			foreach (array_keys(iw::getConfigsByType('ActiveRecord')) as $key) {
				if (fGrammar::underscorize($class) == $key) {
					return TRUE;
				}
			}

			try {
				$schema = fORMSchema::retrieve();
				return in_array(fORM::tablize($class), $schema->getTables());
			} catch (fException $e) {}

			return FALSE;
		}

		/**
		 * Initializses the ActiveRecord class or a child class to be used
		 * as an active record.
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return boolen TRUE if the configuration succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array(), $element = NULL)
		{
			$record_class = fGrammar::camelize($element, TRUE);

			if ($record_class == __CLASS__) {

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

				fORM::registerHookCallback(
					'*',
					'post::store()',
					iw::makeTarget(__CLASS__, 'resetCache')
				);

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
				$ukeys = $schema->getKeys($table, 'unique');
				if (sizeof($ukeys) == 1 && sizeof($ukeys[0]) == 1) {
					self::$info[$record_class]['id_column'] = $ukeys[0][0];
				} else {
					self::$info[$record_class]['id_column'] = NULL;
				}
			}

			if (isset($config['slug_column'])) {

				$valid_slug_column = FALSE;
				$slug_column       = $config['slug_column'];

				if (!isset($ukeys)) {
					$ukeys = $schema->getKeys($table, 'unique');
				}

				foreach ($ukeys as $ukey) {
					if (count($ukey) == 1 && $ukey[0] == $slug_column) {
						$valid_slug_column = $slug_column;
					}
				}

				if (!$valid_slug_column) {
					throw new fProgrammerException (
						'Slug column requires the column to be unique.'
					);
				}

				fORM::registerHookCallback(
					$record_class,
					'pre::validate()',
					iw::makeTarget(__CLASS__, 'validateSlugColumn')
				);

				self::$info[$record_class]['slug_column'] = $slug_column;
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
									self::$imageUploadDirectory .
									implode(DIRECTORY_SEPARATOR, array(
										$table,
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
									self::$fileUploadDirectory .
									implode(DIRECTORY_SEPARATOR, array(
										$table,
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
					iw::makeTarget(__CLASS__, 'validatePasswordColumns')
				);
			}

			// Set all non-configurable information

			self::$info[$record_class]['columns']        = array();
			self::$info[$record_class]['pkey_columns']   = array();
			self::$info[$record_class]['pkey_methods']   = array();
			self::$info[$record_class]['fkey_columns']   = array();
			self::$info[$record_class]['serial_columns'] = array();

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
		 * Dynamically scaffolds an Active Record class.
		 *
		 * @static
		 * @access public
		 * @param string $record_class The class name to scaffold
		 * @return boolean TRUE if the record class was scaffolded, FALSE otherwise
		 */
		static public function __make($record_class)
		{
			$template = implode(DIRECTORY_SEPARATOR, array(
				'classes',
				__CLASS__ . '.php'
			));

			Scaffolder::make($template, array(
				'class' => $record_class
			), __CLASS__);

			if (class_exists($record_class, FALSE)) {
				return TRUE;
			}

			return FALSE;
		}

		/**
		 * Determines if an Active Record class has been defined.
		 *
		 * @static
		 * @access public
		 * @param string $record_class The Active Record class
		 * @return boolean Whether or not the class is defined
		 */
		static public function classExists($record_class)
		{
			return (
				// The class_exists() is a workaround for PHP bug #46753
				// it should not be required as is_subclass_of should
				// properly trigger autoload.  This behavior is fixed
				// in PHP 5.3+
				class_exists($record_class) &&
				is_subclass_of($record_class, __CLASS__)
			);
		}

		/**
		 * Inspects a column on a particular record class.  If this is called
		 * using the inspectColumn() method on an active record it will add
		 * enhanced information.
		 *
		 * @static
		 * @access public
		 * @param string $record_class The Active Record class
		 * @param string $column The name of the column
		 * @param array $info The array of current inspection information
		 * @return array The enhanced inspection information
		 */
		static public function inspectColumn($record_class, $column, &$info = array())
		{

			// TODO: Determine if flourish will cache the $info array with
			// TODO: additional changes here... if not, implement local cache
			// TODO: using self::$inspectionInfo

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


			if ($column == self::getInfo($record_class, 'slug_column')) {
				$info['format'] = 'slug';

			} elseif (in_array($column, $order_columns)) {
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

			$fixed_columns  = self::getInfo($record_class, 'fixed_columns');
			$serial_columns = self::getInfo($record_class, 'serial_columns');
			$info['serial'] = FALSE;

			if (
				$info['format'] == 'ordering'
				|| in_array($column, $fixed_columns)
				|| in_array($column, $serial_columns)
			) {
				$info['fixed'] = TRUE;
			} else {
				$info['fixed'] = FALSE;
			}

			if (in_array($column, $serial_columns)) {
				$info['serial'] = TRUE;
			}

			return $info;
		}

		/**
		 * Converts a record name into a class name, for example: user to
		 * User or user_photograph to UserPhotograph
		 *
		 * @static
		 * @access public
		 * @param string $record The name of the record
		 * @return string|NULL The class name of the record or NULL if it does not exist
		 */
		static public function classFromRecordName($record_name)
		{
			if (!in_array($record_name, self::$nameTranslations)) {
				try {
					$record_class = fGrammar::camelize($record_name, TRUE);
					if (self::classExists($record_class)){
						self::$nameTranslations[$record_class] = $record_name;
					}
				} catch (fProgrammerException $e) {}
			}

			return array_search($record_name, self::$nameTranslations);
		}

		/**
		 * Converts a table name into an active record class name, for example:
		 * users to User
		 *
		 * @static
		 * @access public
		 * @param string $table The name of the table
		 * @return string|NULL The class name of the record or NULL if it does not exist
		 */
		static public function classFromRecordTable($record_table)
		{
			if (!in_array($record_table, self::$tableTranslations)) {
				try {
					$record_class = fORM::classize($record_table);
					if (self::classExists($record_class)){
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
		 * @static
		 * @access public
		 * @param string $recordset The name of the recordset
		 * @return string|NULL The class name of the active record or NULL if it does not exist
		 */
		static public function classFromRecordSet($record_set)
		{
			if (!in_array($record_set, self::$setTranslations)) {
				try {
					$record_class = fGrammar::singularize($record_set);
					if (self::classExists($record_class)){
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
		 * @static
		 * @access public
		 * @param string $entry The entry name to convert
		 * @return string|NULL The class name of the active record or NULL if it does not exist
		 */
		static public function classFromEntry($entry)
		{
			if (!in_array($entry, self::$entryTranslations)) {
				try {
					$singularized = fGrammar::singularize($entry);
					$record_class = fGrammar::camelize($singularized, TRUE);
					if (self::classExists($record_class)){
						self::$entryTranslations[$record_class] = $entry;
					}
				} catch (fProgrammerException $e) {}
			}

			return array_search($entry, self::$entryTranslations);
		}

		/**
		 * Gets the record name for an Active Record class
		 *
		 * @static
		 * @access public
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
		 * @static
		 * @access public
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
		 * @static
		 * @access public
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
		 * @static
		 * @access public
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
		 * @static
		 * @access public
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
		 * @static
		 * @access public
		 * @param string $record_class The name of the active record class
		 * @return boolean TRUE if it is a relationship, FALSE otherwise
		 */
		static public function isRelationship($record_class)
		{
			return self::getInfo($record_class, 'is_relationship');
		}

		/**
		 * Resets some cached information such as the slug and resource keys
		 * in the event related information such as primary key values has
		 * changed.
		 *
		 * @static
		 * @access public
		 * @param fActiveRecord The active record object
		 * @param array $values The new column values being set
		 * @param array $old_values The original column values
		 * @param array $related The related records array for the record
		 * @param array $cache The cache array for the record
		 * @return void
		 */
		static public function resetCache($object, &$values, &$old_values, &$related_records, &$cache)
		{
			$record_class    = get_class($object);
			$slug_column     = self::getInfo($record_class, 'slug_column');
			$pkey_columns    = self::getInfo($record_class, 'pkey_columns');
			$changed_columns = array_keys($old_values);

			if (in_array($slug_column, $changed_columns)) {
				$object->slug = NULL;
			}

			if (count(array_intersect($pkey_columns, $changed_columns))) {
				$object->resourceKey = NULL;
				if ($object->slug) {
					$object->slug = NULL;
				}
			}
		}

		/**
		 * A validation hook for the slug column.  This ensures that the slug
		 * contains only URL safe characters without requiring encoding.
		 *
		 * @static
		 * @access public
		 * @param fActiveRecord The active record object
		 * @param array $values The new column values being set
		 * @param array $old_values The original column values
		 * @param array $related The related records array for the record
		 * @param array $cache The cache array for the record
		 * @param array $validation_messages An array of validation messages
		 * @return void
		 */
		static public function validateSlugColumn($object, &$values, &$old_values, &$related_records, &$cache, &$validation_messages)
		{
			$record_class = get_class($object);
			$slug_column  = self::getInfo($record_class, 'slug_column');
			$url_friendly = fURL::makeFriendly($values[$slug_column]);

			if ($values[$slug_column] == $url_friendly) {
				return;
			}

			$invalid_characters = array_diff(
				str_split(strtolower($values[$slug_column])),
				str_split($url_friendly)
			);

			if (($i = array_search(' ', $invalid_characters)) !== FALSE) {
				$invalid_characters   = array_diff(
					$invalid_characters,
					array(' ')
				);
				$invalid_characters[] = 'spaces';
			}

			if(count($invalid_characters)) {
				$message  = fGrammar::humanize($slug_column) . ': ';
				$message .= 'Cannot contain ' . fGrammar::joinArray(
					$invalid_characters,
					'or'
				);
				$validation_messages[] = $message;
			}
		}

		/**
		 * A validation hook for password columns. If any columns are set as
		 * password columns, this method will be registered to ensure that a
		 * password confirmation field matches the original field when storing
		 * the record or that if a password is already set, an empty value will
		 * result in no change.  In addition, this method ensures the password
		 * is hashed.
		 *
		 * @static
		 * @access public
		 * @param fActiveRecord The active record object
		 * @param array $values The new column values being set
		 * @param array $old_values The original column values
		 * @param array $related The related records array for the record
		 * @param array $cache The cache array for the record
		 * @param array $validation_messages An array of validation messages
		 * @return void
		 */
		static public function validatePasswordColumns($object, &$values, &$old_values, &$related_records, &$cache, &$validation_messages)
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

					if (iw::checkSAPI('cli')) {
						$confirmation = $values[$password_column];
					}

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
		 * Creates a record from a provided resource key.
		 *
		 * @static
		 * @access public
		 * @param string $record_class The Active Record Class
		 * @param string $resource_key A JSON encoded primary key string representation of the record
		 * @return fActiveRecord The active record matching the resource key
		 *
		 */
		static public function createFromResourceKey($record_class, $resource_key)
		{

			if (!self::classExists($record_class)) {
				throw new fProgrammerException(
					'Cannot create record of type %s, missing class',
					$record_class
				);

			} elseif (empty($resource_key)) {
				return new $record_class();
			}

			$resource_key = fJSON::decode($resource_key, TRUE);
			$pkey         = $resource_key['primary_key'];
			$record       = new $record_class($pkey);
			$identifier   = (isset($resource_key['identifier']))
				? $resource_key['identifier']
				: NULL;


			if ($identifier !== NULL) {

				$match_id = $record->__toString();

				if ($identifier != $match_id) {
					throw new fValidationException(
						'Provided identifier does not match.'
					);
				}
			}

			return $record;
		}

		/**
		 * Creates a record from a provided class, slug, and identifier.  The
		 * identifier is optional, but if is provided acts as an additional
		 * check against the validity of the record.
		 *
		 * @static
		 * @access public
		 * @param string $record_class The Active Record class
		 * @param string $slug A URL-friendly primary key string representation of the record
		 * @param string $identifier An optional identifier to check the validity
		 * @return fActiveRecord The active record matching the slug
		 */
		static public function createFromSlug($record_class, $slug, $identifier = NULL)
		{

			if (!self::classExists($record_class)) {
				throw new fProgrammerException(
					'Cannot create record of type %s, missing class',
					$record_class
				);

			} elseif (empty($slug)) {
				return new $record_class();

			} elseif ($column = self::getInfo($record_class, 'slug_column')) {
				return new $record_class(array(
					$column => $slug
				));
			}

			$columns = self::getInfo($record_class, 'pkey_columns');
			$data    = explode(self::$fieldSeparator, $slug, count($columns));

			if (sizeof($data) < sizeof($columns)) {
				throw new fProgrammerException(
					'Malformed slug for class %s, check the primary key.',
					$record_class
				);

			} elseif (count($columns) == 1) {
				$pkey = $data[0];

			} else {
				foreach ($columns as $column) {
					$pkey[$column] = array_shift($data);
				}
			}

			$record = new $record_class($pkey);

			if ($identifier !== NULL) {

				$match_id = fURL::makeFriendly(
					$record->__toString(),
					NULL,
					self::$wordSeparator
				);

				if ($identifier != $match_id) {
					throw new fValidationException(
						'Provided identifier does not match.'
					);
				}
			}

			return $record;
		}

		/**
		 * Gets record information on a particular Active Record class.
		 *
		 * @static
		 * @access private
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
