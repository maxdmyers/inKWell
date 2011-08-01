<?php

	/**
	 * The Auth Record model is an abstract base class for Users and AuthRoles
	 * which provides functionality for various permission operations.
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 * @copyright Copyright (c) 2011, Matthew J. Sahagian
	 * @license http://www.gnu.org/licenses/agpl.html GNU Affero General Public License
	 *
	 * @package inKWell::Extensions::Auth
	 */
	abstract class AuthRecord extends ActiveRecord
	{

		const PERMISSION_DEFINITION_PREFIX = 'PERM';

		/**
		 * Initializes all static class information for AuthRecords.
		 *
		 * @static
		 * @access protected
		 * @param array $config The configuration array
		 * @param array $element The element name of the configuration array
		 * @return void
		 */
		static public function __init(array $config = array(), $element = NULL)
		{
			// Don't do any configuration for ourself, but if it's a child
			// class pass it on up the line.

			if ($element === fGrammar::underscorize(__CLASS__)) {
				define('IS_CREATOR', 1);
				foreach (AuthActions::build() as $auth_action) {
					$permission = implode('_', array(
						self::PERMISSION_DEFINITION_PREFIX,
						strtoupper($auth_action->getName())
					));

					define($permission, pow(2, $auth_action->getId()));
				}
				return TRUE;
			}

			return parent::__init($config, $element);
		}

		/**
		 * Fetches an auth record's permission for a given type, key, and field.
		 * This is different than an ACL check, in that it is not the combined
		 * result of user + roles on a User record.  If there is no exact match
		 * for a permission, the permission record will be created.  If the
		 * inherit parameter is set to true, that record will have a bit_value
		 * of the existing permission that matches it the most closely.
		 *
		 * @access public
		 * @param string $type The type of record (a record name)
		 * @param string $key The resource key for the record
		 * @param string $field The field to check (a column or property)
		 * @param boolean $inherit Whether or not to assign calculated bi_value to new permissions
		 * @return mixed The permission record (UserPermission, RolePermission)
		 */
		 public function fetchPermission($type = NULL, $key = NULL, $field = NULL, $inherit = FALSE)
		 {
		 	$record_class     = get_class($this);
		 	$record_table     = ActiveRecord::getRecordTable($record_class);
		 	$permission_class = $record_class . 'Permission';
			$permission_table = ActiveRecord::getRecordTable($permission_class);
			$schema           = fORMSchema::retrieve($permission_class);

			foreach ($schema->getKeys($permission_table, 'foreign') as $fkey) {
				if (
					   $fkey['foreign_table']  == $record_table
					&& $fkey['foreign_column'] == 'id'
				) {
					$id_column = $fkey['column'];
				}
			}

		 	try {
		 		$permission = new $permission_class(array(
		 			$id_column => $this->getId(),
		 			'type'          => $type,
		 			'key'           => $key,
		 			'field'         => $field
		 		));

		 	} catch (fNotFoundException $e) {
				$permission = new $permission_class();
				$permission->set($id_column, $this->getId());
				$permission->setType($type);
				$permission->setKey($key);
				$permission->setField($field);

				$type  = ($type)
					? array($type, NULL)
					: NULL;

				$key   = ($type && $key)
					? array($key, NULL)
					: NULL;

				$field = ($type && $field)
					? array($field, NULL)
					: NULL;

				if ($inherit) {

					$permissions = RecordSet::build($permission_class, array(
						$id_column . '=' => $this->getId(),
						'type='          => $type,
						'key='           => $key,
						'field='         => $field
					));

					$best_match_score   = -1;
					$matched_permission = NULL;

					foreach ($permissions as $candidate_permission) {
						$score = array_sum(array(
							$candidate_permission->getType()  ? 4 : 0,
							$candidate_permission->getKey()   ? 2 : 0,
							$candidate_permission->getField() ? 1 : 0
						));

						if ($score > $best_match_score) {
							$matched_permission = $candidate_permission;
						}
					}
				}

				if (isset($matched_permission)) {
					$bit_value = intval($matched_permission->getBitValue());
				} else {
					$bit_value = 0;
				}

				$permission->setBitValue($bit_value);

		 	}

			return $permission;
		 }

		/**
		 * Checks whether or not the auth record has a particular permission
		 * granted.
		 *
		 * @access public
		 * @param integer $permission The permission value to check for
		 * @param string $type The type of record (a record name)
		 * @param string $key The resource key for the record
		 * @param string $field The field to check (a column or property)
		 * @param boolean $inherit Whether or not to assign calculated bit_value to new permissions
		 * @return boolean returns TRUE of the auth record has permission, FALSE otherwise
		 */
		public function checkPermission($permission, $type = NULL, $key = NULL, $field = NULL, $inherit = FALSE)
		{
			try {
				$record = $this->fetchPermission($type, $key, $field, $inherit);
				$result = intval($record->getBitValue()) & $permission;
				return ($result == $permission);
			} catch (fNotFoundException $e) {}
			return FALSE;
		}

		/**
		 * Grants permissions on a particular permission record for the auth
		 * record.
		 *
		 * @access public
		 * @param integer $permission The permission value to check for
		 * @param string $type The type of record (a record name)
		 * @param string $key The resource key for the record
		 * @param string $field The field to check (a column or property)
		 * @return void
		 */
		public function grantPermission($permission, $type = NULL, $key = NULL, $field = NULL)
		{
			$record    = $this->fetchPermission($type, $key, $field, TRUE);
			$bit_value = intval($record->getBitValue());
			$new_value = $bit_value | $permission;
			$record->setBitValue($new_value)->store();
		}

		/**
		 * Revokes permissions on a particular permission record for the auth
		 * record.
		 *
		 * @access public
		 * @param integer $permission The permission value to check for
		 * @param string $type The type of record (a record name)
		 * @param string $key The resource key for the record
		 * @param string $field The field to check (a column or property)
		 * @return void
		 */
		public function revokePermission($permission, $type = NULL, $key = NULL, $field = NULL)
		{
			$record    = $this->fetchPermission($type, $key, $field, TRUE);
			$bit_value = intval($record->getBitValue());
			$new_value = $bit_value & ~$permission;
			$record->setBitValue($new_value)->store();
		}
	}
