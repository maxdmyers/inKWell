<div class="primary section">
	<div class="messages section">
		<? fMessaging::show(Controller::MSG_TYPE_ALERT,   iw::makeTarget('AuthController', 'login'))  ?>
		<? fMessaging::show(Controller::MSG_TYPE_ERROR,   iw::makeTarget('AuthController', 'login'))  ?>
		<? fMessaging::show(Controller::MSG_TYPE_SUCCESS, iw::makeTarget('AuthController', 'login'))  ?>
	</div>
	<form action="" method="post">
		<fieldset>
			<label for="login-username">Username</label>
			<input id="login-username" type="text" name="username" value="<?= fRequest::encode('username') ?>" />
			<label for="login-password">Password</label>
			<input id="login-password" type="password" name="password" />
		</fieldset>
		<fieldset class="action section">
			<button type="submit">
				Login
			</button>
		</fieldset>
	</form>
</div>
