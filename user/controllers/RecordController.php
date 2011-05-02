<?php

	/**
	 * The Active Record Controller.  This controller is used to provide basic
	 * CRUMS (like CRUD) functionality to Active Records.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	abstract class RecordController extends Controller
	{

		const DEFAULT_RECORDS_PER_PAGE = 20;

		/**
		 * An array of child class information.
		 *
		 * @static
		 * @access private
		 * @var array
		 */
		static private $recordControllerConfigs = array();

		/**
		 * Prepares a new RecordController establishing the appropriate default
		 * views, and any common data.
		 *
		 * @param string $controller_class The name of the controller class being prepared
		 * @return void
		 */
		protected function prepare($controller_class)
		{
			$action       = self::getAction();
			$record_class = self::getRecordClass($controller_class);
			$entry        = ActiveRecord::getEntry($record_class);

			$view_file    = implode(DIRECTORY_SEPARATOR, array(
				$entry,              // custom views directory
                $action . '.php'     // file
			));

			if (!View::exists($view_file)) {

				$view_file = implode(DIRECTORY_SEPARATOR, array(
					'active_records',    // default active record views
					$action . '.php'     // file
				));
			}

			$this->view->load($view_file);
		}

		/**
		 * Initializes all static class information for the RecordController
		 *
		 * @param array $config The configuration array
		 * @param string $element The element name of the configuration array
		 * @return boolean TRUE if initialization succeeds, FALSE otherwise
		 */
		static public function __init(array $config = array(), $element = NULL)
		{

			$controller_class = fGrammar::camelize($element, TRUE);

			if ($controller_class == __CLASS__) {

				// TODO: Make the parent class more configurable

			} elseif (!is_subclass_of($controller_class, __CLASS__)) {
				return FALSE;
			}

			self::$recordControllerConfigs[$controller_class] = array();

			$per_page = (isset($config['record_per_page']))
				? $config['records_per_page']
				: self::DEFAULT_RECORDS_PER_PAGE;

			$class_config =& self::$recordControllerConfigs[$controller_class];
			$class_config = array(
				'per_page' => $per_page
			);

			return TRUE;
		}

		/**
		 * Dynamically defines a record controller if the provided class
		 * is an Active Record class.  The newly created controller class
		 * extends the default functionality found here.
		 *
		 * @param string $controller_class The controller class name to dynamically define
		 * @return boolean TRUE if a record controller was dynamically defined, FALSE otherwise
		 */
		static public function __make($controller_class)
		{

			$record_set   = self::getRecordSet($controller_class);
			$record_class = ActiveRecord::classFromRecordSet($record_set);

			if ($record_class) {

				$supported_actions = array();

				foreach (AuthActions::build()->call('getName') as $action) {
					if (method_exists(__CLASS__, $action)) {
						$supported_actions[] = $action;
					}
				}

				$template = implode(DIRECTORY_SEPARATOR, array(
					'classes',
					__CLASS__ . '.php'
				));

				Scaffolder::make($template, array(
					'class'             => $controller_class,
					'supported_actions' => $supported_actions
				), __CLASS__);

				if (class_exists($controller_class, FALSE)) {
					return TRUE;
				}
			}

			return FALSE;
		}


		/**
		 * Converts a Record Entry name into a RecordController class, for
		 * example: article_images to ArticleImagesController
		 *
		 * @param string $record_entry The name of the Record Entry
		 * @return string|NULL The RecordController class or NULL if it does not exist
		 */
		static protected function classFromEntry($record_entry)
		{
			return fGrammar::camelize($record_entry, TRUE) . self::SUFFIX;
		}

		/**
		 * Converts a Active Record class into an RecordController class, for
		 * example: User to UsersController
		 *
		 * @param string $record_class The name of the Active Record class
		 * @return string|NULL The RecordController class or NULL if it does not exist
		 */
		static protected function classFromRecordClass($record_class)
		{
			// TODO: Write function See also: getRecordClass() and __init()
		}

		/**
		 * Determines the appropriate Record Set for which a given
		 * RecordController class is responsible, for example UsersController to
		 * Users
		 * @param string $controller_class The ARConctroller class name
		 * @return string The name of the Record Set
		 */
		static protected function getRecordSet($controller_class)
		{
			// TODO: Write Function with classFromRecordClass and __init()
			// TODO: to make record class configurable, right now it
			// TODO: returns based on simple convention

			return str_replace(self::SUFFIX, '', $controller_class);
		}

		/**
		 * Determines the appropriate Active Record for which a given
		 * RecordController class is responsible, for example: UsersController to User
		 *
		 * @param string $controller_class The RecordController class name
		 * @return string The name of the Active Record class name
		 */
		static protected function getRecordClass($controller_class)
		{

			$record_set = self::getRecordSet($controller_class);
			return ActiveRecord::classFromRecordSet($record_set);
		}

		/**
		 * A standard active record create method
		 *
		 * @param string $controller_class The controller class to use.
		 * @return View The view object.
		 */
		static protected function create($controller_class)
		{

			$controller     = new $controller_class();
			$record_class   = self::getRecordClass($controller_class);
			$record_set     = self::getRecordSet($controller_class);
			$entry          = ActiveRecord::getEntry($record_class);
			$record_name    = ActiveRecord::getRecordName($record_class);
			$action         = __FUNCTION__;
			$schema         = fORMSchema::retrieve();

			// Check for a related entry

			$related_entry  = fRequest::get('related_entry', 'string', NULL);
			$related_slug   = fRequest::get('related_slug',  'string', NULL);
			$related_record = NULL;

			if ($related_entry && $related_slug) {

				$related_class = ActiveRecord::classFromEntry($related_entry);

				try {
					$related_record = ActiveRecord::createFromSlug(
						$related_class,
						$related_slug
					);
				} catch (fNotFoundException $e) {
					self::triggerError('not_found');
				}
			}

			// Create the record

			try {

				$active_record = new $record_class();

				if (fRequest::isPost()) {

					fRequest::validateCSRFToken(fRequest::get('auth_token'));
					$active_record->populate();

					if ($related_record) {

						$building_method  = 'build' . $record_set;
						$associate_method = 'associate' . $record_set;

						$related_record->$associate_method($related_record
							->$building_method()
							->merge($active_record)
						);


						$related_record->store();

					} else {

						$active_record->store();
					}

					$target = iw::makeTarget($controller_class, 'manage');

					fMessaging::create('success', $target, sprintf(
						'The %s "<em>%s</em>" was successfully created.',
						fGrammar::humanize($record_class),
						fHTML::prepare($active_record)
					));

					$affected_records = array($active_record->makeSlug(FALSE));
					fSession::set('affected_records', $affected_records);

					self::redirect($target, ($related_record)
						? array(
							':related_entry' => $related_entry,
							':related_slug'  => $related_slug
						)
						: array()
					);
				}

			} catch (fValidationException $e) {
				$target = iw::makeTarget ($controller_class, __FUNCTION__);
				fMessaging::create('error', $target, $e->getMessage());
			}

			$human_record    = fGrammar::humanize($record_class);
			$title           = sprintf('Create a new %s', $human_record);
			$record_table    = ActiveRecord::getRecordTable($record_class);
			$column_info     = $schema->getColumnInfo($record_table);
			$primary_columns = array();

			foreach ($column_info as $column => $info) {

				// Get custom info

				ActiveRecord::inspectColumn($record_class, $column, $info);

				if (
					// Don't show serial columns
					!$info['serial']                            &&
					// Don't show fixed columns
					!$info['fixed']                             &&
					// Don't show foreign key columns
					!$info['is_fkey']                           &&
					// Don't show dates, times, or timestamps that allow NULL
					// or have default values
					(
						!(
							$info['type'] == 'date'             ||
							$info['type'] == 'time'             ||
							$info['type'] == 'timestamp'
						)                                       ||
						(
							!$info['default']                   &&
							$info['not_null']
						)
					)
				) {

					$primary_columns[$column] = $info;

				}
			}

			// Set and render our view

			$controller->view
				 -> pack ('controller_class', $controller_class)
				 -> pack ('title',            $title)
				 -> pack ('active_record',    $active_record)
				 -> pack ('entry',            $entry)
				 -> pack ('record_name',      $record_name)
				 -> pack ('action',           $action)
				 -> pack ('primary_columns',  $primary_columns)
				 -> pack ('related_record',   $related_record);

			if (self::checkEntryAction($controller_class, __FUNCTION__)) {

				$page = new PagesController();

				$page->view
					-> add  ('contents',   $controller->view)
					// -> add  ('scripts',           '/user/styles/lightbox.css')
					// -> add  ('scripts',           '/user/scripts/lightbox.js')
					// -> add  ('scripts',           '/user/scripts/admin/forms.js')
					-> add  ('scripts',           '/user/scripts/ckeditor/ckeditor.js')
					-> add  ('scripts',           '/user/scripts/ckeditor/adapters/jquery.js')
					-> pack ('id',                $action)
					-> push ('title',             $title)
					-> push ('classes',           $entry)
					-> render();

				return $page->view;
			}

			return $controller->view;

		}

		/**
		 * A standard active record remove method
		 */
		static protected function remove($controller_class)
		{

			$controller     = new $controller_class();
			$slug           = fRequest::get('slug', 'string?');
			$record_class   = self::getRecordClass($controller_class);
			$record_set     = self::getRecordSet($controller_class);
			$entry          = ActiveRecord::getEntry($record_class);

			// Check for a related entry

			$related_entry  = fRequest::get('related_entry', 'string', NULL);
			$related_slug   = fRequest::get('related_slug', 'string', NULL);
			$related_record = NULL;

			if ($related_entry && $related_slug) {

				$related_class = ActiveRecord::classFromEntry($related_entry);

				try {
					$related_record = ActiveRecord::createFromSlug(
						$related_class,
						$related_slug
					);
				} catch (fNotFoundException $e) {
					self::triggerError('not_found');
				}
			}

			if (fRequest::isPost()) {

				$record_slugs = fRequest::get($entry, 'array', array());

				if ($slug) {
					$record_slugs[] = $slug;
				}

				foreach ($record_slugs as $slug) {
					try {
						$record = ActiveRecord::createFromSlug($record_class, $slug);
						$record->delete();
					} catch (fNotFoundException $e) {
						$affected_records[] = $slug;
					}
				}

				$target = iw::makeTarget($controller_class, 'manage');

				if (isset($affected_records)) {
					fMessaging::create('error', $target, sprintf(
						'We were unable to remove %s.  Those that failed are highlighted below.',
						fGrammar::inflectOnQuantity(
							sizeof($record_slugs),
							'the record',
							'all the records'
						)
					));

					fSession::set('affected_records', $affected_records);

				} else {

					fMessaging::create('success', $target, sprintf(
						'Successfully removed <em>%s</em> %s %s',
						$num_records = sizeof($record_slugs),
						fGrammar::humanize($record_class),
						fGrammar::inflectOnQuantity(
							$num_records,
							'record',
							'records'
						)
					));
				}

				self::redirect($target, ($related_record)
					? array(
						':related_entry' => $related_entry,
						':related_slug'  => $related_slug
					)
					: array()
				);


			}

			if (self::checkEntryAction($controller_class, __FUNCTION__)) {

				// This implies a user is removing a specific primary key
				// a view should be displayed to confirm the removal

			}

		}

		/**
		 * A standard active record update method
		 */
		static protected function update($controller_class)
		{

			$controller     = new $controller_class();
			$record_class   = self::getRecordClass($controller_class);
			$record_set     = self::getRecordSet($controller_class);
			$entry          = ActiveRecord::getEntry($record_class);
			$record_name    = ActiveRecord::getRecordName($record_class);

			$action         = __FUNCTION__;
			$schema         = fORMSchema::retrieve();

			// Check for a related entry

			$related_entry  = fRequest::get('related_entry', 'string', NULL);
			$related_slug   = fRequest::get('related_slug', 'string', NULL);
			$related_record = NULL;

			if ($related_entry && $related_slug) {

				$related_class = ActiveRecord::classFromEntry($related_entry);

				try {
					$related_record = ActiveRecord::createFromSlug(
						$related_class,
						$related_slug
					);
				} catch (fNotFoundException $e) {
					self::triggerError('not_found');
				}
			}

			// check for primary entry

			$slug = fRequest::get('slug', 'string?');

			if ($slug) {

				try {

					$active_record = ActiveRecord::createFromSlug(
						$record_class,
						$slug
					);

					if (fRequest::isPost()) {

						fRequest::validateCSRFToken(fRequest::get('auth_token'));
						$active_record->populate();
						$active_record->store();

						$target = iw::makeTarget($controller_class, 'manage');

						fMessaging::create('success', $target, sprintf(
							'The %s "<em>%s</em>" was successfully updated.',
							fGrammar::humanize($record_class),
							fHTML::prepare($active_record)
						));

						$affected_records = array($active_record->makeSlug(FALSE));
						fSession::set('affected_records', $affected_records);

						self::redirect($target, ($related_record)
							? array(
								':related_entry' => $related_entry,
								':related_slug'  => $related_slug
							)
							: array()
						);
					}

				} catch (fValidationException $e) {

					$target = iw::makeTarget ($controller_class, __FUNCTION__);
					fMessaging::create('error', $target, $e->getMessage());

				} catch (fNotFoundException $e) {

					$target = iw::makeTarget($controller_class, 'manage');
					fMessaging::create('error', $target, $e->getMessage());

				}

			} else {

				$records = fRequest::get($entry, 'array', array());

				if (fRequest::isPost()) {

					foreach ($records as $slug => $column_values) {

						try {

							$record = ActiveRecord::createFromSlug(
								$record_class,
								$slug
							);

							foreach ($column_values as $column => $value) {
								$method = 'set' . fGrammar::camelize($column, TRUE);
								$record->$method($value);
							}

							$record->store();

						} catch (fValidationException $e) {
							$affected_records[] = $slug;
						} catch (fNotFoundException $e) {
							$affected_records[] = $slug;
						}

						$target = iw::makeTarget($controller_class, 'manage');

						if (isset($affected_records)) {
							fMessaging::create('error', $target,
								'Some records failed to update, they are highlighted below'
							);

							fSession::set('affected_records', $affected_records);
						} else {
							fMessaging::create('success', $target,
								'All records were updated successfully!'
							);
						}

						self::redirect($target, ($related_record)
							? array(
								':related_entry' => $related_entry,
								':related_slug'  => $related_slug
							)
							: array()
						);

					}
				}
			}

			$title           = sprintf('Update %s', $active_record);
			$record_table    = ActiveRecord::getRecordTable($record_class);
			$column_info     = $schema->getColumnInfo($record_table);
			$primary_columns = array();

			foreach ($column_info as $column => $info) {

				// Get custom info

				ActiveRecord::inspectColumn($record_class, $column, $info);

				if (
					// Don't show serial columns
					!$info['serial']                            &&
					// Don't show fixed columns
					!$info['fixed']                             &&
					// Don't show foreign key columns
					!$info['is_fkey']                           &&
					// Don't show dates, times, or timestamps that allow NULL
					// or have default values
					(
						!(
							$info['type'] == 'date'             ||
							$info['type'] == 'time'             ||
							$info['type'] == 'timestamp'
						)                                       ||
						(
							!$info['default']                   &&
							$info['not_null']
						)
					)
				) {

					$primary_columns[$column] = $info;

				}
			}

			// Set and render our view

			$controller->view
				 -> pack ('controller_class', $controller_class)
				 -> pack ('title',            $title)
				 -> pack ('active_record',    $active_record)
				 -> pack ('entry',            $entry)
				 -> pack ('record_name',      $record_name)
				 -> pack ('action',           $action)
				 -> pack ('primary_columns',  $primary_columns);

			if (self::checkEntryAction($controller_class, __FUNCTION__)) {

				$page = new PagesController();

				$page->view
					-> add  ('contents',   $controller->view)
					// -> add  ('scripts',           '/user/styles/lightbox.css')
					// -> add  ('scripts',           '/user/scripts/lightbox.js')
					// -> add  ('scripts',           '/user/scripts/admin/forms.js')
					-> add  ('scripts',           '/user/scripts/ckeditor/ckeditor.js')
					-> add  ('scripts',           '/user/scripts/ckeditor/adapters/jquery.js')
					-> pack ('id',                $action)
					-> push ('title',             $title)
					-> push ('classes',           $entry)
					-> render();

				return $page->view;
			}

			return $controller->view;

		}

		/**
		 * A standard active record manage method
		 */
		static protected function manage($controller_class)
		{

			// Check for sub-actions

			$valid_actions = array('', 'create', 'remove', 'update');

			if (($action = fRequest::getValid('action', $valid_actions))) {
				$target = iw::makeTarget($controller_class, $action);
				self::exec($target);
			} else {
				$action = __FUNCTION__;
			}

			// Initialize the controller and some general information

			$controller   = new $controller_class();
			$record_class = $controller->getRecordClass();
			$entry        = ActiveRecord::getEntry($record_class);
			$record_table = ActiveRecord::getRecordTable($record_class);
			$record_set   = ActiveRecord::getRecordSet($record_class);
			$title        = fGrammar::humanize($action . $record_set);
			$schema       = fORMSchema::retrieve();
			$class_config = self::$recordControllerConfigs[$controller_class];

			// Check for a related entry

			$related_entry  = fRequest::get('related_entry', 'string', NULL);
			$related_slug   = fRequest::get('related_slug', 'string', NULL);
			$related_record = NULL;

			if ($related_entry && $related_slug) {

				$related_class = ActiveRecord::classFromEntry($related_entry);

				try {
					$related_record = ActiveRecord::createFromSlug(
						$related_class,
						$related_slug
					);
				} catch (fNotFoundException $e) {
					self::triggerError('not_found');
				}
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
						$sql_filter = str_replace('*', '%', $filter_value);
						$filters = array(
							$filter_column . '~' => $sql_filter
						);
						break;
				}

			} elseif (empty($filter_value)) {

				$filter_column = NULL;
			}

			// Check for sorting

			$sorting        = ActiveRecord::getOrder($record_class);
			$sort_column    = fRequest::get('sort_column', 'string?');
			$sort_direction = fRequest::get('sort_direction', 'string', 'asc');

			if ($sort_column) {
				$sorting = array($sort_column => $sort_direction);
			}

			// Define the default display, filter, and sortable columns.

			$column_info        = $schema->getColumnInfo($record_table);
			$filterable_formats = array('string', 'email');
			$sortable_formats   = array('string', 'email', 'float', 'date', 'timestamp');

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
					// Don't show full text columns
					$info['type']   !== 'text'                              &&
					// Don't show foreign key columns
					!$info['is_fkey']                                       &&
					// Don't show auto-increment columns
					!$info['serial']                                        &&
					// Here we go:
					(
						// Show any e-mail, file, or image columns
						$info['format'] == 'email'                          ||
						$info['format'] == 'image'                          ||
						$info['format'] == 'file'                           ||
						// Show all varchar fields less than 128 chars
						(
							$info['type']       == 'varchar'                &&
							$info['max_length'] <= 128						||
							$info['format']     == 'image'
						)                                                   ||
						// Show any remaining floats, integers, and chars
						$info['type'] == 'float'                            ||
						$info['type'] == 'integer'                          ||
						$info['type'] == 'char'                             ||
						// One more level!
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
				}
			}

			$filter_columns = array_keys(
				array_intersect($display_columns, $filterable_formats)
			);

			if (!in_array('ordering', $display_columns)) {
				$sortable_columns = array_keys(
					array_intersect($display_columns, $sortable_formats)
				);
			}

			// Get the page

			$page     = fRequest::get('page', 'integer', 1);
			$per_page = $class_config['per_page'];

			// Build the actual record set

			$active_record_set = call_user_func(
				iw::makeTarget($record_set, 'build'),
				$filters,
				$sorting,
				$per_page,
				$page
			);

			// Get the intersection with associated records

			if ($related_record) {

				$building_method = 'build' . $record_set;

				$active_record_set = $active_record_set->intersect(
					$related_record->$building_method()
				);
			}

			// Display different messages depending on whether or not the user was attempting to filter results

			if (!$active_record_set->count()) {
				if (!count($filters) && !$active_record_set->count(TRUE)) {
					fMessaging::create('helper', Moor::getActiveCallback(), sprintf(
						'There are currently no %s records, you can create one <a href="%s">here</a>.',
						fGrammar::humanize($record_class),
						'create'
					));
				} else {
					fMessaging::create('alert', Moor::getActiveCallback(), sprintf(
						'There were no %s records found matching your query, please try again',
						fGrammar::humanize($record_class)
					));
				}
			}


			$no_limit_count = $active_record_set->count(TRUE);
			$page_count     = ceil($no_limit_count / $per_page);

			// Set the affected records on delete and render the view

			$affected_records = fSession::delete('affected_records');

			// Find related records

			$child_entries    = array();
			$sibling_entries  = array();

			$children = $schema->getRelationships($record_table, 'one-to-many');
			$siblings = $schema->getRelationships($record_table, 'one-to-one');

			foreach ($children as $child) {
				$related_entry = ActiveRecord::getEntry(
					$related_record_class = ActiveRecord::classFromRecordTable(
						$child['related_table']
					)
				);

				$child_entries[$related_entry] = self::classFromEntry(
					$related_entry
				);
			}

			foreach ($siblings as $sibling) {
				$related_entry = ActiveRecord::getEntry(
					$related_record_class = ActiveRecord::classFromRecordTable(
						$sibling['related_table']
					)
				);

				$sibling_entries[$related_entry] = self::classFromEntry(
					$related_entry
				);
			}

			// Set and render our view

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
				 -> pack ('affected_records',  $affected_records)
				 -> pack ('related_record',    $related_record)
				 -> pack ('related_entry',     $related_entry)
				 -> pack ('child_entries',     $child_entries)
				 -> pack ('sibling_entries',   $sibling_entries)
				 -> pack ('page_count',        $page_count)
				 -> pack ('page',              $page);

			if (self::checkEntryAction($controller_class, __FUNCTION__)) {

				$page = new PagesController();

				$page->view
					-> add  ('contents',   $controller->view)
					// -> add  ('scripts',           '/user/styles/lightbox.css')
					// -> add  ('scripts',           '/user/scripts/lightbox.js')
					// -> add  ('scripts',           '/user/scripts/admin/forms.js')
					-> pack ('id',                $action)
					-> push ('title',             $title)
					-> push ('classes',           $entry)
					-> render();

				return $page->view;
			}

			return $controller->view;
		}
	}
