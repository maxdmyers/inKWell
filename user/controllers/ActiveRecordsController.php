<?php

	/**
	 * The Active Records Controller
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class ActiveRecordsController extends Controller
	{

		const RECORDS_PER_PAGE = 20;

		protected $page = NULL;

		/**
		 * Prepares a new ActiveRecordsController for running actions.
		 *
		 * @param string $record_class
		 * @return void
		 */
		protected function prepare()
		{

			AuthorizationController::requireLoggedIn();
			AuthorizationController::requireACL($this->entry, PERM_SHOW);

			$record_class = constant(get_class($this) . '::RECORD_CLASS');

			$this->data   = array(
				'record_class'     => $record_class,
				'record'           => ActiveRecord::getRecord($record_class),
				'record_table'     => ActiveRecord::getRecordTable($record_class),
				'record_set'       => ActiveRecord::getRecordSet($record_class),
				'entry'            => ActiveRecord::getEntry($record_class)
			);

			if (self::checkRequestFormat('html')) {

				$view_files[] = array(
					implode(DIRECTORY_SEPARATOR, array(
						$this->entry,          // path
						$this->action . '.php' // file
					)),
					implode(DIRECTORY_SEPARATOR, array(
					'active_records',       // default active record views
					$this->action . '.php' // file
					))
				);

				$this->view->load($view_files);
			}


		}

		/**
		 * Renders a particular ActiveRecordController supported view on.
		 *
		 * @param string $view The view to render if you so choose to support multiple views per format
		 * @return void
		 */
		protected function render($view = NULL)
		{

			switch (iw::makeTarget(self::getRequestFormat(), $view)) {

				case 'html::create':
				case 'html::update':

					$this->view->load(self::requestView($view_file))

						 -> add  ('scripts',           '/support/scripts/lightbox.js')
						 -> add  ('scripts',           '/support/scripts/admin/forms.js')
						 -> pack ('page_id',           $this->action)
						 -> pack ('controller_class',  $this->controller_class)
						 -> pack ('action',            $this->action)
						 -> pack ('record',            $this->record)
						 -> pack ('entry',             $this->entry)
						 -> pack ('input_columns',     $this->input_columns)
						 -> pack ('active_record',     $this->active_record)
						 -> push ('page_title',        fGrammar::humanize($this->action . $this->record_class))
						 -> push ('page_classes',      $this->entry);
					return;

				case 'html::manage':

					$this->view->load(self::requestView($view_file))

						 -> pack ('controller_class',  $this->controller_class)
						 -> pack ('action',            $this->action)
						 -> pack ('entry',             $this->entry)
						 -> pack ('active_record_set', $this->active_record_set)
						 -> pack ('display_columns',   $this->display_columns)
						 -> pack ('filter_columns',    $this->filter_columns)
						 -> pack ('filter_column',     $this->filter_column)
						 -> pack ('filter_value' ,     $this->filter_value)
						 -> pack ('sortable_columns',  $this->sortable_columns)
						 -> pack ('sort_column' ,      $this->sort_column)
						 -> pack ('sort_direction',    $this->sort_direction)
						 -> pack ('affected_records',  $this->affected_records);

					return;
			}

		}


		/**
		 * A standard active record create method
		 */
		static protected function create()
		{

			$schema           = fORMSchema::retrieve();
			$record_class     = $this_class::RECORD_CLASS;

			try {

				$active_record = new $record_class();

				if (fRequest::isPost()) {

					$active_record->populate();
					$active_record->store();

					fMessaging::create('success', iw::makeTarget($controller_class, 'manage'), sprintf(
						'The %s "<em>%s</em>" was successfully created.',
						fGrammar::humanize($record_class),
						fHTML::prepare($active_record)
					));

					$affected_records = array($active_record->makeSlug(FALSE));
					fSession::set('affected_records', $affected_records);

					fURL::redirect(Moor::linkTo(iw::makeTarget($controller_class, 'manage')));
				}



			} catch (fValidationException $e) {
				fMessaging::create('error', iw::makeTarget($controller_class, 'create'), $e->getMessage());
			}

			$column_info   = $schema->getColumnInfo($controller->record_table);
			$input_columns = array();

			foreach ($column_info as $column => $info) {

				if (ActiveRecord::isReadOnlyColumn($record_class, $column)) {
					continue;
				}

				// determine the type

				switch($info['type']) {

					case 'varchar':
						// TODO: Possible selects for foreign key columns
						if ($info['valid_values']) {
							$input_info['type'] = 'select';
						} elseif (ActiveRecord::isImageColumn($record_class, $column)) {
							$input_info['type'] = 'image';
						} elseif (ActiveRecord::isFileColumn($record_class, $column)) {
							$input_info['type'] = 'file';
						} elseif (ActiveRecord::isPasswordColumn($record_class, $column)) {
							$input_info['type'] = 'password';
						} else {
							$input_info['type'] = 'string';
						}
						break;

					default:

					case 'text':
						$input_info['type'] = 'textarea';
						break;

					case 'integer':
					case 'float':
						$input_info['type']       = 'text';
						$input_info['max_length'] = 15;
						break;

					case 'date':
						$input_info['type']       = 'date';
						$input_info['max_length'] = 32;
						break;
					case 'time':
						$input_info['type']       = 'time';
						$input_info['max_length'] = 32;
						break;
					case 'timestamp':
						$input_info['type']       = 'timestamp';
						$input_info['max_length'] = 32;
						break;
					case 'boolean':
						$input_info['type'] = 'checkbox';
						break;

				}
				$input_columns[$column] = $input_info;
			}
			$controller->input_columns = $input_columns;

			$controller->active_record = $active_record;
			$controller->render('create');
		}

		/**
		 * A standard active record remove method
		 */
		static protected function remove()
		{

			$pkey             = fRequest::get('pkey', 'string?');
			$record_class     = $controller_class::RECORD_CLASS;

			if (fRequest::isPost()) {

				$record_pkeys = fRequest::get($controller->entry, 'array', array());

				if ($pkey) {
					$record_pkeys[] = $pkey;
				}

				foreach ($record_pkeys as $pkey) {
					try {
						$record = ActiveRecord::createFromSlug($record_class, $pkey);
						$record->delete();
					} catch (fNotFoundException $e) {
						$affected_records[] = $pkey;
					}
				}

				if (isset($affected_records)) {
					fMessaging::create('error', iw::makeTarget($controller_class, 'manage'), sprintf(
						'We were unable to remove %s',
						fGrammar::inflectOnQuantity(sizeof($record_pkeys), 'the record', 'all the records')
					));

					fSession::set('affected_records', $affected_records);
				} else {
					fMessaging::create('success', iw::makeTarget($controller_class, 'manage'), sprintf(
						'Successfully removed <em>%s</em> %s %s',
						$num_records = sizeof($record_pkeys),
						fGrammar::humanize($record_class),
						fGrammar::inflectOnQuantity($num_records, 'record', 'records')
					));
				}

			}

			// fURL::redirect(Moor::linkTo(iw::makeTarget($controller_class, 'manage')));

		}

		/**
		 * A standard active record update method
		 */
		static protected function update()
		{
			$record_class     = $controller_class::RECORD_CLASS;

			try {

				$pkey   = fRequest::get('pkey', 'string?');
				$id     = fRequest::get('id', 'string?');
				$record = ActiveRecord::createFromSlug($record_class, $pkey, $id);

				if (fRequest::isPost()) {

					$record->populate();
					$record->store();

					fMessaging::create('success', iw::makeTarget($controller_class, 'manage'), sprintf(
						'The %s "<em>%s</em>" was successfully updated.',
						fGrammar::humanize($record_class),
						fHTML::prepare($record)
					));

					$affected_records = array($record->makeSlug(FALSE));
					fSession::set('affected_records', $affected_records);

					fURL::redirect(Moor::linkTo(iw::makeTarget($controller_class, 'manage')));
				}

			} catch (fNotFoundException $e) {

			} catch (fValidationException $e) {
				fMessaging::create('error', iw::makeTarget($controller_class, 'update'), $e->getMessage());
			}

			$controller->record = $record;
			$controller->render('update');
		}

		/**
		 * A standard active record manage method
		 */
		static protected function manage()
		{

			$valid_actions = array('', 'create', 'remove', 'update');

			if (($action = fRequest::getValid('action', $valid_actions))) {
				$this->$action();
			} else {
				$action = 'manage';
			}

			$controller_class = get_class($this);
			$record_class     = $this->getRecordClass();
			$schema           = fORMSchema::retrieve();

			// Check for filters

			$filters                   = array();
			$this->filter_column = fCRUD::getSearchValue('filter_column');
			$this->filter_value  = fCRUD::getSearchValue('filter_value');

			if ($this->filter_column && $this->filter_value) {

				$filter_column_info = $schema->getColumnInfo($this->record_table, $this->filter_column);

				switch ($filter_column_info['type']) {
					case 'date':
						// Special date stuff for interpreting *'s
					default:
						$translated_filter_value = str_replace('*', '%', $this->filter_value);
						$filters = array(
							$this->filter_column . '~' => $translated_filter_value
						);
						break;
				}

			}

			// Check for sorting

			$sorting                    = ActiveRecord::getDefaultSorting($record_class);
			$this->sort_column    = fRequest::get('sort_column', 'string?');
			$this->sort_direction = fRequest::get('sort_direction', 'string', 'asc');

			if ($this->sort_column) {
				$sorting = array($this->sort_column => $this->sort_direction);
			}

			// Check for pagination

			$page = 1;


			// Define the default display, filter, and sortable columns.

			$column_info    = $schema->getColumnInfo($this->record_table);
			$filter_types   = array('varchar', 'char');
			$sortable_types = array('varchar', 'char', 'float', 'date', 'timestamp');

			foreach ($column_info as $column => $info) {
				// The longest if statement in the WORLD!
				if (
					// Don't show password columns
					!ActiveRecord::isPasswordColumn($record_class, $column) &&
					// Don't show foreign key columns
					!ActiveRecord::isFKeyColumn($record_class, $column)     &&
					// Don't show full text columns
					$info['type'] !== 'text'                                &&
					// Don't show auto-increment columns
					!$info['auto_increment']                                &&
					// Show all varchar, float, and char fields
					(
						$info['type'] == 'varchar'                          ||
						$info['type'] == 'float'                            ||
						$info['type'] == 'char'                             ||
						(
							// Show dates, times, and timestamps with default
							// values or where they are not null
							(
								$info['type'] == 'date'                     ||
								$info['type'] == 'time'                     ||
								$info['type'] == 'timestamp'
							)                                               &&
							(
								$info['default']                            ||
								$info['not_null']
							)
						)
					)

				) {


					if (ActiveRecord::isImageColumn($record_class, $column)) {
						$display_columns[$column] = 'image';
					} elseif (ActiveRecord::isFileColumn($record_class, $column)) {
						$display_columns[$column] = 'file';
					} else {
						$display_columns[$column] = $info['type'];

						if(in_array($info['type'], $filter_types)) {
							$filter_columns[] = $column;
						}

						if (in_array($info['type'], $sortable_types)) {
							$sortable_columns[] = $column;
						}
					}
				}
			}

			// Set the display, filter, and sortable columns.

			$this->display_columns  = (isset($display_columns))  ? $display_columns  : array();
			$this->filter_columns   = (isset($filter_columns))   ? $filter_columns   : array();
			$this->sortable_columns = (isset($sortable_columns)) ? $sortable_columns : array();

			// Build the actual record set

			$this->active_record_set = call_user_func(
				iw::makeTarget($this->record_set, 'build'),
				$filters,
				$sorting,
				self::RECORDS_PER_PAGE,
				$page
			);

			// Display different messages depending on whether or not the user was attempting to filter results

			if (!$this->active_record_set->count()) {
				if (!$this->filter_column) {
					fMessaging::create('helper', Moor::getActiveCallback(), sprintf(
						'There are currently no %s records, you can create one <a href="%s">here</a>.',
						fGrammar::humanize($record_class),
						Moor::linkTo(iw::makeTarget($this, 'create'))
					));
				} else {
					fMessaging::create('alert', Moor::getActiveCallback(), sprintf(
						'There were no %s records matching your query, please try again',
						fGrammar::humanize($record_class)
					));
				}
			}

			// Set the records, affected_records and render the view

			$this->affected_records = fSession::get('affected_records');
			$this->render('manage');

			// Delete no longer needed affected_records

			fSession::delete('affected_records');

			if ($this->isEntryPoint(__FUNCTION__)) {

				$page       = new PageController();
				$page_title = fGrammar::humanize($action . $this->record_set);

				$page->view
					-> add  ('primary_content',   $this->view)
					-> add  ('scripts',           '/support/scripts/lightbox.js')
					-> add  ('scripts',           '/support/scripts/admin/forms.js')
					-> pack ('id',                $action)
					-> push ('title',             $page_title)
					-> push ('classes',           $this->entry);

				$page->view->render();

			}


		}

		/**
		 * Initializes the ActiveRecord Controller
		 */
		static public function __init()
		{
		}

		/**
		 * Maps out all the active records' entries and controllers for
		 * tables in the public schema in the format
		 * array('entry' => 'EntryController')...
		 *
		 * @param void
		 * @return void
		 */
		static public function getMap()
		{
			$schema = fORMSchema::retrieve();
			$tables = $schema->getTables();
			$cmap   = array();

			foreach ($tables as $table) {
				$record_class = ActiveRecord::classFromRecordTable($table);
				if ($record_class && ActiveRecord::canMap($record_class)) {
					$entry        = ActiveRecord::getEntry($record_class);
					$record_set   = ActiveRecord::getRecordSet($record_class);
					$cmap[$entry] = $record_set . self::CONTROLLER_SUFFIX;
				}
			}

			return $cmap;
		}

		/**
		 * Dynamically defines an record controller if the provided class
		 * is an Active Record class.  The newly created controller class
		 * extends the default functionality found here.
		 *
		 * @param string $class The controller class name to dynamically define
		 * @return boolean TRUE if a record controller was dynamically defined, FALSE otherwise
		 */
		static public function __define($controller_class)
		{
			$record_set   = str_replace(self::CONTROLLER_SUFFIX, '', $controller_class);
			$record_class = ActiveRecord::classFromRecordSet($record_set);
			if ($record_class && ActiveRecord::canMap($record_class)) {

				$supported_actions = array();
				foreach (AuthActions::build()->call('getName') as $action) {
					if (method_exists(__CLASS__, $action)) {
						$supported_actions[] = $action;
					}
				}

				eval(Scaffolder::makeClass($controller_class, __CLASS__, array(
					'record_class'      => $record_class,
					'supported_actions' => $supported_actions
				)));

				if (class_exists($controller_class, FALSE)) {
					return TRUE;
				}
			}
			return FALSE;
		}

	}
