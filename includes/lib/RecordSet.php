<?php

	/**
	 * RecordSet class for aggregated arrays of Active Records
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class RecordSet extends fRecordSet implements inkwell
	{

		/**
		 * Matches whether or not a given class name is a potential
		 * RecordSet
		 *
		 * @param string $class The name of the class to check
		 * @return boolean TRUE if it matches, FALSE otherwise
		 */
		static public function __match($class) {
			return TRUE;
		}

		/**
		 * Dynamically defines a RecordSet if the provided class is the
		 * pluralized version of an ActiveRecord class.
		 *
		 * @param string $record_set_class The Class name to dynamically define
		 * @return boolean TRUE if a recordset was dynamically defined, FALSE otherwise
		 */
		static public function __make($record_set)
		{
			if ($record_class = ActiveRecord::classFromRecordSet($record_set)) {

				Scaffolder::makeClass($record_set, __CLASS__, array(
					'active_record' => $record_class
				));

				if (class_exists($record_set, FALSE)) {
					return TRUE;
				}
			}
			return FALSE;
		}

	}
