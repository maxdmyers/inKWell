<div class="primary section">
	<h2><?= end($this->data['page_title']) ?></h2>

	<? foreach ($message_types = array('alert', 'helper', 'error', 'success') as $message_type) {
		fMessaging::show($message_type, iw::makeTarget($this->data['controller_class'], $this->data['action']));
	} ?>

	<form action="" method="post">
		<? include 'fieldsets.php'; ?>
		<fieldset class="actions">
			<button type="submit"><?= fGrammar::humanize($this->data['action']) ?></button>
		</fieldset>
	</form>
</div>
