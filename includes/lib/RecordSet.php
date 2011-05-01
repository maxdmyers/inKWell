<?php

	/**
	 * RecordSet class for aggregated arrays of Active Records
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell
	 */
	abstract class RecordSet extends fRecordSet implements inkwell
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

			if (class_exists($record_set, FALSE)) {
				return TRUE;
			}

			return FALSE;
		}

	}
