<?php

	/**
	 * RecordSet class for aggregated arrays of Active Records
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class RecordSet extends fRecordSet
	{

		/**
		 * Dynamically defines a RecordSet if the provided class is the
		 * pluralized version of an ActiveRecord class.
		 *
		 * @param string $record_set_class The Class name to dynamically define
		 * @return boolean TRUE if a recordset was dynamically defined, FALSE otherwise
		 */
		static public function __define($record_set_class)
		{
			if ($record_class = ActiveRecord::classFromRecordSet($record_set_class)) {

				eval(Scaffolder::makeClass($record_set_class, __CLASS__, array(
					'active_record' => $record_class
				)));

				if (class_exists($record_set_class, FALSE)) {
					return TRUE;
				}
			}
			return FALSE;
		}

	}
