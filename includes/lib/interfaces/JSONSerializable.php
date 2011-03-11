<?php

	/**
	 * Provides the JSONSerializable Interface if it does not exist
	 *
	 * @copyright  Copyright (c) 2011 Matthew J. Sahagian
	 * @author     Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 * @license    http://flourishlib.com/license
	 *
	 * @package    Flourish
	 * @link       http://flourishlib.com/fJSON
	 *
	 * @changes    1.0.0b   The initial implementation [mjs, 2011-07-12]
	 */
	interface JSONSerializable
	{
		/**
		 * Gets an alternative data structure to be serialized
		 *
		 * @access public
		 * @param void
		 * @return mixed The data structure to be serialized
		 */
		public function jsonSerialize();
	}

