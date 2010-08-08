<div class="header section">
	<h1>inKWell Console</h1>
	<p class="login_message">
		<? if (User::checkLoggedIn()) { ?>
			Welcome <?= User::retrieveLoggedIN()->prepareUsername() ?> (<a href="/logout">logout</a>)
		<? } ?>
	</p>
</div>
<ul class="primary nav section">
	<li>
		<a href="/admin/">Home</a>
	</li>
	<? if (User::checkLoggedIn()) { ?>
		<? foreach(array('users' => 'UsersController') as $entry => $controller) { ?>
			<? if (User::checkACL($entry, PERM_SHOW)) { ?>
				<li class="<?= $this->selectOn(array('page_classes' => $entry)) ?>">
					<a href="<?= Moor::linkTo($controller . '::manage') ?>"><?= ucwords(fGrammar::humanize($entry)) ?></a>
					<ul class="secondary nav section">
						<li class="<?= $this->selectOn(array('page_id' => 'manage')) ?>">
							<a href="<?= Moor::linkTo($controller . '::manage') ?>">
								<? if (User::checkACL($entry, array(PERM_UPDATE, PERM_REMOVE))) { ?>
									Manage
								<? } else { ?>
									List
								<? } ?>
								<?= fGrammar::humanize($entry) ?>
							</a>
						</li>
						<? if(User::checkACL($entry, PERM_CREATE)) { ?>
							<li class="<?= $this->selectOn(array('page_id' => 'create')) ?>">
								<a href="<?= Moor::linkTo($controller . '::create') ?>">Create a new Record</a>
							</li>
						<? } ?>
						<? if (User::checkACL('user_permissions-' . $entry, PERM_UPDATE) || User::checkACL('auth_role_permissions-' . $entry, PERM_UPDATE)) { ?>
							<li class="<?= $this->selectOn(array('page_id' => 'update', 'page_classes' => 'access_controls')) ?>">
								<a href="<?= Moor::linkTo('ACLController::update :resource_key', $entry) ?>">Access Control List</a>
							</li>
						<? } ?>
					</ul>
				</li>
			<? } ?>
		<? } ?>
	<? } ?>
</ul>
