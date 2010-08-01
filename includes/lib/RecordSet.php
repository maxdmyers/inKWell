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
				self::defineForRecord($record_class, $record_set_class);
				return TRUE;
			}
			return FALSE;
		}

		/**
		 * Creates a basic record set class for the provided active record
		 * class.  This is useful to get the standard build functionality.
		 *
		 * @param string $record_class The active record class
		 * @param string $record_set_class The class name for the newly defined record set class
		 * @return void
		 */
		static protected function defineForRecord($record_class, $record_set_class)
		{
			$variable = '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*';
			$is_valid  = (
				preg_match('#' . $variable . '#', $record_set_class) &&
				is_subclass_of($record_class, 'ActiveRecord')
			);

			if ($is_valid) {
				eval(Scaffolder::makeClass($record_set_class, __CLASS__, array(
					'active_record' => $record_class
				)));
			}
		}

	}
