	/**
	 * The <?= $class ?> is a recordset representing a collection of
	 * <?= $active_record ?> objects / records.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class <?= $class ?> extends <?= $parent_class ?>

	{

		/**
		 * Initializes the <?= $class ?> Record Set
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
		}

		/**
		 * Builds a recordset using an array of where conditions and ordering
		 * information.
		 *
		 * @param array $wheres
		 * @param array $ordering
		 * @param integer $limit
		 * @param integer $page
		 * @return
		 */
		static public function build(array $wheres = array(), array $ordering = array(), $limit = NULL, $page = NULL)
		{
			if (!sizeof($ordering)) {
				try {
					$ordering = <?= $active_record ?>::getOrder();
				} catch (fProgrammerException $e) {}
			}
			return parent::build('<?= $active_record ?>', $wheres, $ordering, $limit, $page);
		}

		/**
		 *
		 */
		static public function buildFromSQL($sql, $no_limit_sql)
		{
			return parent::build('<?= $active_record ?>', $sql, $no_limit_sql);
		}

<? if (!$scaffolding) { ?>
		/**
		 * Allows for a dynamically created record set to be scaffolded.
		 *
		 * @param string $file
		 * @return void
		 */
		static public function __scaffold($file = NULL) {

			if (!$file) {
				$file = implode(DIRECTORY_SEPARATOR, array(
					$_SERVER['DOCUMENT_ROOT'], // document_root
					'models', 'sets',          // path
					__CLASS__ . '.php'         // file
				));
			}

			Scaffolder::writeClass($file, __CLASS__, '<?= $parent_class ?>', array(
				'active_record'     => '<?= $active_record ?>'
			));
		}
<? } ?>

		// Custom Object Methods

		// Custom Class Methods

	}
