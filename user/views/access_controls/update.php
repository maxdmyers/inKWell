<h2>Access Control List</h2>
<form action="" method="post">
	<fieldset>
		<legend>Authorization Roles</legend>
		<table>
			<thead>
				<tr>
					<th class="first">Authorization Role</th>
					<? foreach($this->data['auth_actions'] as $auth_action) { ?>
						<th><?= ucwords($auth_action->prepareName()) ?> <?= fGrammar::humanize($this->data['resource_key']) ?></th>
					<? } ?>
				</tr>
			</thead>
			<tbody>
				<? foreach($this->data['auth_roles'] as $auth_role) { ?>
					<? if (User::checkACL($auth_role->makeResourceKey(), PERM_SHOW)) { ?>
						<tr class="<?= $this->positionIn('auth_roles', $auth_role) ?>">
							<td class="first">
								<?= $auth_role->prepareName() ?>
								<input type="hidden" name="auth_roles[<?= $auth_role->getId() ?>][<?= $this->data['resource_key'] ?>][]" value="0" />
							</td>
							<? foreach ($this->data['auth_actions'] as $auth_action) { ?>
								<td>
									<input
										type="checkbox"
										name="auth_roles[<?= $auth_role->getId() ?>][<?= $this->data['resource_key'] ?>][]"
										value="<?= $auth_action->getBitValue() ?>"
										<? if ($auth_role->checkPermission($this->data['resource_key'], $auth_action->getBitValue(), TRUE)) { ?>
											<?= 'checked="checked"' ?>
										<? } ?>
										<? if (!User::checkACL('auth_role_permissions-' . $auth_role->makeResourceKey(), PERM_UPDATE)) { ?>
											<?= 'disabled="disabled"' ?>
										<? } ?>
									/>
								</td>
							<? } ?>
						</tr>
					<? } ?>
				<? } ?>
			</tbody>
		</table>
	</fieldset>

	<fieldset>
		<legend>Users</legend>
		<table>
			<thead>
				<tr>
					<th class="first">User</th>
					<? foreach($this->data['auth_actions'] as $auth_action) { ?>
						<th><?= ucwords($auth_action->prepareName()) ?> <?= fGrammar::humanize($this->data['resource_key']) ?></th>
					<? } ?>
				</tr>
			</thead>
			<tbody>
				<? foreach($this->data['users'] as $user) { ?>
					<? if (User::checkACL($user->makeResourceKey(), PERM_SHOW)) { ?>
						<tr class="<?= $this->positionIn('users', $user) ?>">
							<td class="first">
								<?= $user->prepareUsername() ?>
								<input type="hidden" name="users[<?= $user->getId() ?>][<?= $this->data['resource_key'] ?>][]" value="0" />
							</td>
							<? foreach ($this->data['auth_actions'] as $auth_action) { ?>
								<td>
									<input
										type="checkbox"
										name="auth_roles[<?= $auth_role->getId() ?>][<?= $this->data['resource_key'] ?>][]"
										value="<?= $auth_action->getBitValue() ?>"
										<? if ($user->checkPermission($this->data['resource_key'], $auth_action->getBitValue(), TRUE)) { ?>
											<?= 'checked="checked"' ?>
										<? } ?>
										<? if (!User::checkACL('user_permissions-' . $user->makeResourceKey(), PERM_UPDATE)) { ?>
											<?= 'disabled="disabled"' ?>
										<? } ?>
									/>
								</td>
							<? } ?>
						</tr>
					<? } ?>
				<? } ?>
			</tbody>
		</table>
	</fieldset>


	<fieldset class="actions">
		<button type="submit">Save</button>
	</fieldset>
</form>
