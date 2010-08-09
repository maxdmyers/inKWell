<?php

	/**
	 * The Active Records Controller
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class ARController extends Controller
	{

		const RECORDS_PER_PAGE = 20;

		protected $page = NULL;

		/**
		 * Prepares a new ARController for running actions.
		 *
		 * @param string $record_class
		 * @return void
		 */
		protected function prepare($controller_class)
		{
			iw::loadClass('AuthController');

			if (!User::checkLoggedIn()) {
				self::triggerError('not_authorized');
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

						 -> add  ('styles',            '/user/scripts/lightbox.css')
						 -> add  ('scripts',           '/user/scripts/lightbox.js')
						 -> add  ('scripts',           '/user/scripts/admin/forms.js')
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

					return;
			}

		}


		/**
		 * A standard active record create method
		 */
		static protected function create($controller_class)
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
		static protected function remove($controller_class)
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
		static protected function update($controller_class)
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
		static protected function manage($controller_class)
		{

			// Check for sub-actions

			$valid_actions = array('', 'create', 'remove', 'update');

			if (($action = fRequest::getValid('action', $valid_actions))) {
				$this->$action();
			} else {
				$action = 'manage';
			}

			// Initialize the controller and some general information

			$controller   = new $controller_class();
			$record_class = $controller->getRecordClass();
			$entry        = ActiveRecord::getEntry($record_class);
			$record_table = ActiveRecord::getRecordTable($record_class);
			$record_set   = ActiveRecord::getRecordSet($record_class);
			$title        = fGrammar::humanize($action . $record_set);
			$schema       = fORMSchema::retrieve();

			if (!User::checkACL($entry, PERM_SHOW)) {
				self::triggerError('forbidden');
			}

			if (self::checkRequestFormat('html')) {

				$view_file = implode(DIRECTORY_SEPARATOR, array(
					'active_records',          // default active record views
					$action . '.php'     // file
				));

				$controller->view->load($view_file);
			}


			// Check for filters

			$filters       = array();
			$filter_column = fCRUD::getSearchValue('filter_column');
			$filter_value  = fCRUD::getSearchValue('filter_value');

			if ($filter_column && $filter_value) {

				$filter_column_info = $schema->getColumnInfo($record_table, $filter_column);

				switch ($filter_column_info['type']) {
					case 'date':
						// Special date stuff for interpreting *'s
					default:
						$translated_filter_value = str_replace('*', '%', $filter_value);
						$filters = array(
							$filter_column . '~' => $translated_filter_value
						);
						break;
				}

			}

			// Check for sorting

			$sorting        = ActiveRecord::getOrder($record_class);
			$sort_column    = fRequest::get('sort_column', 'string?');
			$sort_direction = fRequest::get('sort_direction', 'string', 'asc');

			if ($sort_column) {
				$sorting = array($sort_column => $sort_direction);
			}

			// Check for pagination

			$page = 1;


			// Define the default display, filter, and sortable columns.

			$column_info      = $schema->getColumnInfo($record_table);
			$filter_types     = array('varchar', 'char');
			$sortable_types   = array('varchar', 'char', 'float', 'date', 'timestamp');

			// Set the display, filter, and sortable columns.

			$display_columns  = array();
			$filter_columns   = array();
			$sortable_columns = array();

			foreach ($column_info as $column => $info) {

				// Get custom info

				ActiveRecord::inspectColumn($record_class, $column, $info);

				// The longest if statement in the WORLD!
				if (
					// Don't show password columns
					$info['format'] !== 'password'                          &&
					// Don't show foreign key columns
					!$info['is_fkey']                                       &&
					// Don't show full text columns
					$info['type'] !== 'text'                                &&
					// Don't show auto-increment columns
					!$info['serial']                                        &&
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

					$display_columns[$column] = $info['format'];

					if(in_array($info['type'], $filter_types)) {
						$filter_columns[] = $column;
					}

					if (in_array($info['type'], $sortable_types)) {
						$sortable_columns[] = $column;
					}

				}
			}

			// Build the actual record set

			$active_record_set = call_user_func(
				iw::makeTarget($record_set, 'build'),
				$filters,
				$sorting,
				self::RECORDS_PER_PAGE,
				$page
			);

			// Display different messages depending on whether or not the user was attempting to filter results

			if (!$active_record_set->count()) {
				if (!$filter_column) {
					fMessaging::create('helper', Moor::getActiveCallback(), sprintf(
						'There are currently no %s records, you can create one <a href="%s">here</a>.',
						fGrammar::humanize($record_class),
						Moor::linkTo(iw::makeTarget($controller_class, 'create'))
					));
				} else {
					fMessaging::create('alert', Moor::getActiveCallback(), sprintf(
						'There were no %s records matching your query, please try again',
						fGrammar::humanize($record_class)
					));
				}
			}

			// Set the records, affected_records and render the view

			$affected_records = fSession::get('affected_records');
			fSession::delete('affected_records');

			$controller->view
				 -> pack ('controller_class',  $controller_class)
				 -> pack ('action',            $action)
				 -> pack ('entry',             $entry)
				 -> pack ('title',             $title)
				 -> pack ('active_record_set', $active_record_set)
				 -> pack ('display_columns',   $display_columns)
				 -> pack ('filter_columns',    $filter_columns)
				 -> pack ('filter_column',     $filter_column)
				 -> pack ('filter_value' ,     $filter_value)
				 -> pack ('sortable_columns',  $sortable_columns)
				 -> pack ('sort_column' ,      $sort_column)
				 -> pack ('sort_direction',    $sort_direction)
				 -> pack ('affected_records',  $affected_records);

			if (self::isEntryAction($controller_class, __FUNCTION__)) {

				$page = new PagesController();

				$page->view
					-> add  ('primary_section',   $controller->view)
					-> add  ('scripts',           '/user/styles/lightbox.css')
					-> add  ('scripts',           '/user/scripts/lightbox.js')
					-> add  ('scripts',           '/user/scripts/admin/forms.js')
					-> pack ('id',                $action)
					-> push ('title',             $title)
					-> push ('classes',           $entry)
					-> render();

				return $page->view;
			}

			return $controller->view;
		}

		/**
		 * Initializes the ActiveRecord Controller
		 */
		static public function __init()
		{
		}

		/**
		 * Dynamically defines an record controller if the provided class
		 * is an Active Record class.  The newly created controller class
		 * extends the default functionality found here.
		 *
		 * @param string $class The controller class name to dynamically define
		 * @return boolean TRUE if a record controller was dynamically defined, FALSE otherwise
		 */
		static public function __make($controller_class)
		{
			$record_set   = str_replace(self::CONTROLLER_SUFFIX, '', $controller_class);
			$record_class = ActiveRecord::classFromRecordSet($record_set);
			if ($record_class) {

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

	}
