
	/**
	 * The <?= $class ?>
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class <?= $class ?> extends <?= $parent_class ?>

	{

		/**
		 * Prepares a new <?= $class ?> for running actions.
		 *
		 * @param string void
		 * @return void
		 */
		protected function prepare()
		{
			// The controller prepare method should be called only if you
			// are building out full pages or responses, not for controllers
			// which only provide embeddable views.
			//
			// return parent::prepare(__CLASS__);
		}

		/**
		 * Initializes all static class information for the <?= $class ?>
		 *
		 * @param array $config The configuration array
		 * @return boolean TRUE if initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array())
		{
			// All custom initialization goes here, make sure to check any
			// configuration you're setting up for errors and return FALSE
			// in the event the class cannot be initialized with the provided
			// configuration.

			return TRUE;
		}
	}
