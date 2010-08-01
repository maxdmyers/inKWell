<?
	if (!isset($supported_actions)) {
		throw new fProgrammerException (
			'ActiveRecordsController requires an array $supported_actions to scaffold.'
		);
	} elseif(!is_array($supported_actions)) {
		throw new fProgrammerException (
			'ActiveRecordsController requires $supported_actions to be an array'
		);
	}

	if (!isset($record_class)) {
		throw new fProgrammerException (
			'ActiveRecordsController requires a active record class $record_class'
		);
	} elseif(!is_subclass_of($record_class, 'ActiveRecord')) {
		throw new fProgrammerException (
			'ActiveRecordsController requires $record_class to be a sub-class of ActiveRecord'
		);
	}
?>
	/**
	 * The <?= $class ?> is an active record controller responsible for
	 * providing the needed entry point handlers, view rendering, etc for
	 * the supported actions on the <?= $record_class ?> class.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class <?= $class ?> extends <?= $parent_class ?>

	{

		const RECORD_CLASS = '<?= $record_class ?>';

		/**
		 * Prepares a new <?= $class ?> for running actions.
		 *
		 * @param string void
		 * @return void
		 */
		protected function prepare()
		{
			return parent::prepare(self::RECORD_CLASS);
		}

		/**
		 * Renders a particular <?= $class ?> supported view.
		 *
		 * @param string $view The view to render if you so choose to support multiple views per format
		 * @return void
		 */
		protected function render($element)
		{
			return parent::render($element);
		}

<? foreach($supported_actions as $action) { ?>
		/**
		 * Entry handler for the <?= $action ?> action.
		 *
		 * @param void
		 * @return void
		 */
		static public function <?= $action ?>() {
			return parent::<?= $action ?>();
		}

<? } ?>

<? if (!$scaffolding) { ?>
		/**
		 * Allows for a dynamically created active record to be scaffolded.
		 *
		 * @param string $file
		 * @return void
		 */
		static public function scaffold($file = NULL) {

			if (!$file) {
				$file = implode(DIRECTORY_SEPARATOR, array(
					$_SERVER['DOCUMENT_ROOT'], // document_root
					'controllers',             // path
					__CLASS__ . '.php'         // file
				));
			}

			Scaffolder::writeClass($file, __CLASS__, '<?= $parent_class ?>', array(
				'supported_actions' => <?= Scaffolder::export_var($supported_actions) ?>,
				'record_class'      => <?= Scaffolder::export_var($record_class)      ?>
			));

		}
<? } ?>
		// Custom Class Methods

	}
