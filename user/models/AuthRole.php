<?php

	/**
	 * AuthRole
	 *
	 * @author Matthew J. Sahagian [mjs] <gent@dotink.org>
	 */
	class AuthRole extends ActiveRecord
	{

		/**
		 * How to display the auth role record if it is used as a string
		 *
		 * @param void
		 * @return string The name of the auth role
		 */
		public function __toString()
		{
			return $this->getName();
		}

		/**
		 * Fetches an auth role's permission for the given resource key.  If the
		 * permission does not exist in the database then the record is created
		 * with no permissions.
		 *
		 * @param string $resource_key The resource key to fetch permissions for
		 * @param boolean $inherit Whether or not to inherit permissions if there is no specific permission
		 * @return AuthRolePermission The AuthRolePermission record representing the resource key
		 */
		public function fetchPermission($resource_key, $inherit= FALSE)
		{
			try {
				$auth_role_permission = new AuthRolePermission(array(
					'auth_role_id' => $this->getId(),
					'resource_key' => $resource_key
				));
			} catch (fNotFoundException $e) {
				$auth_role_permission = new AuthRolePermission();
				$auth_role_permission->setAuthRoleId($this->getId());
				$auth_role_permission->setResourceKey($resource_key);

				if ($inherit) {

					$best_precision        = -1;
					$auth_role_permissions = AuthRolePermissions::build(array(
						'auth_role_id=' => $this->getId()
					));

					foreach ($auth_role_permissions as $auth_role_permission) {

						$stored_resource_key = $auth_role_permission->getResourceKey();

						if (!$stored_resource_key || strpos($resource_key, $stored_resource_key) === 0) {
							$match_precision = strlen($stored_resource_key) / strlen($resource_key);
							if ($match_precision > $best_precision) {
								$best_matched_permission = $auth_role_permission;
								$best_precision         = $match_precision;
							}
						}
					}
				}

				if (isset($best_matched_permission)) {
					$bit_value = intval($best_matched_permission->getBitValue());
				} else {
					$bit_value = 0;
				}

				$auth_role_permission->setBitValue($bit_value);
			}

			return $auth_role_permission;
		}

		/**
		 * Checks whether or not the auth role record has a particular permission
		 * granted.
		 *
		 * @param string $resource_key The resource key to check permissions on
		 * @param integer $permission The permission value to check for
		 * @param boolean $inherit Whether or not to inherit permissions if there is no specific permission
		 * @return boolean returns TRUE of the auth role record has permission, FALSE otherwise
		 */
		public function checkPermission($resource_key, $permission, $inherit = FALSE)
		{
			try {
				$auth_role_permission = $this->fetchPermission($resource_key, $inherit);
				$result = intval($auth_role_permission->getBitValue()) & $permission;
				return ($result == $permission);
			} catch (fNotFoundException $e) {}
			return FALSE;
		}

		/**
		 * Grants permissions on a particular resource key for the auth role
		 *
		 * @param string $resource_key The resource key to grant permissions on
		 * @param integer $permission The bit value of permissions to grant
		 * @return void
		 */
		public function grantPermission($resource_key, $permission)
		{
			$auth_role_permission = $this->fetchPermission($resource_key, TRUE);
			$new_permissions = intval($auth_role_permission->getBitValue()) | $permission;
			$auth_role_permission->setBitValue($new_permissions)->store();
		}

		/**
		 * Revokes permissions on a particular resource key for the auth role
		 *
		 * @param string $resource_key The resource key to grant permissions on
		 * @param integer $permission The bit value of permissions to revoke
		 * @return void
		 */
		public function revokePermission($resource_key, $permission)
		{
			$auth_role_permission = $this->fetchPermission($resource_key, TRUE);
			$new_permissions = intval($auth_role_permission->getBitValue()) & ~$permission;
			$auth_role_permission->setBitValue($new_permissions)->store();
		}

		/**
		 * Initializes all static class information for AuthRole model
		 *
		 * @param array $config The configuration array
		 * @return void
		 */
		static public function __init($config)
		{
			parent::__init($config, __CLASS__);
		}

		/**
		 * Gets the record name for the AuthRole class
		 *
		 * @return string The custom or default record translation
		 */
		static public function getRecordName()
		{
			return parent::getRecordName(__CLASS__);
		}

		/**
		 * Gets the record table name for the AuthRole class
		 *
		 * @return string The custom or default record table translation
		 */
		static public function getRecordTable()
		{
			return parent::getRecordTable(__CLASS__);
		}

		/**
		 * Gets the record set name for the AuthRole class
		 *
		 * @return string The custom or default record set translation
		 */
		static public function getRecordSet()
		{
			return parent::getRecordSet(__CLASS__);
		}

		/**
		 * Gets the entry name for the AuthRole class
		 *
		 * @return string The custom or default entry translation
		 */
		static public function getEntry()
		{
			return parent::getEntry(__CLASS__);
		}

		/**
		 * Gets the order for the AuthRole class
		 *
		 * @return array The default sort array
		 */
		static public function getOrder()
		{
			return parent::getOrder(__CLASS__);
		}

		/**
		 * Determines whether the record class only serves as a relationship,
		 * i.e. a many to many table.
		 *
		 * @return boolean TRUE if it is a relationship, FALSE otherwise
		 */
		static public function isRelationship()
		{
			return parent::isRelationship(__CLASS__);
		}


		/**
		 * Creates a new AuthRole from a slug and identifier.  The
		 * identifier is optional, but if is provided acts as an additional
		 * check against the validity of the record.
		 *
		 * @param $slug A primary key string representation or "slug" string
		 * @param $identifier The identifier as provided as an extension to the slug
		 * @return fActiveRecord The active record matching the slug
		 */
		static public function createFromSlug($slug, $identifier = NULL)
		{
			return parent::createFromSlug(__CLASS__, $slug, $identifier);
		}

	}
