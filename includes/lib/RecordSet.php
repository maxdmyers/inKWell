<?php

	/**
	 * RecordSet class for aggregated arrays of Active Records
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2012, Matthew J. Sahagian
	 * @license Please reference the LICENSE.txt file at the root of this distribution
	 *
	 * @package inKWell
	 */
	abstract class RecordSet extends fRecordSet implements inkwell, JSONSerializable
	{
		/**
		 * Matches whether or not a given class name is a potential
		 * RecordSet
		 *
		 * @static
		 * @access public
		 * @param string $class The name of the class to check
		 * @return boolean TRUE if it matches, FALSE otherwise
		 */
		static public function __match($class) {
			try {
				$record_class = fGrammar::singularize($class);
				return ActiveRecord::__match($record_class);
			} catch (fProgrammerException $e) {}

			return FALSE;
		}

		/**
		 * Initializes all static class information for the RecordSet
		 *
		 * @static
		 * @access public
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return boolean TRUE if initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array(), $element = NULL)
		{
			return TRUE;
		}

		/**
		 * Dynamically scaffolds a Record Set
		 *
		 * @static
		 * @access public
		 * @param string $record_set The Class name to dynamically define
		 * @return boolean TRUE if a recordset was dynamically defined, FALSE otherwise
		 */
		static public function __make($record_set)
		{
			$record_class = ActiveRecord::classFromRecordSet($record_set);
			$template     = implode(DIRECTORY_SEPARATOR, array(
				'classes',
				__CLASS__ . '.php'
			));

			Scaffolder::make($template, array(
				'class'         => $record_set,
				'active_record' => $record_class
			), __CLASS__);

			if (self::classExists($record_set, FALSE)) {
				return TRUE;
			}

			return FALSE;
		}

		/**
		 * Determines if a Record Set class has been defined by ensuring the class exists
		 * and it is a subclass of RecordSet.  This is, in part, a workaround for a PHP bug
		 * #46753 where is_subclass_of() will not properly autoload certain classes in edge cases.
		 * This behavior is fixed in 5.3+, but the method will probably remain as a nice shorthand.
		 *
		 * @static
		 * @access public
		 * @param string $record_class The Record Set class
		 * @return boolean Whether or not the class is defined
		 */
		static public function classExists($record_set)
		{
			return (class_exists($record_set) && is_subclass_of($record_set, __CLASS__));
		}

		/**
		 * Preps the RecordSet for JSON Serialization
		 *
		 * @access public
		 * @return array A JSON encodable array of all records in the set
		 */
		public function jsonSerialize()
		{
			return $this->getRecords();
		}

	}