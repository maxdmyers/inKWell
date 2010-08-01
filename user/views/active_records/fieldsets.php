<fieldset>
<? foreach ($this->data['input_columns'] as $column => $info) {
	$encode  = 'encode'  . fGrammar::camelize($column, TRUE);
	$inspect = 'inspect' . fGrammar::camelize($column, TRUE);
	?>
	<label><?= fGrammar::humanize($column) ?></label>

	<? if ($info['type'] == 'string') { ?>
		<input type="text" name="<?= $this->data['record'] ?>[<?= $column ?>]" value="<?= $this->data['active_record']->$encode() ?>" />
	<? } ?>

	<? if ($info['type'] == 'password') { ?>
		<input type="password" name="<?= $this->data['record'] ?>[<?= $column ?>]" value="" />
	<? } ?>

	<? if ($info['type'] == 'select') { ?>
		<select name="<?= $this->data['record'] ?>[<?= $column ?>]">
			<? if (!($default = $this->data['active_record']->$inspect('default'))) { ?>

			<? } ?>

			<? foreach ($this->data['active_record']->$inspect('valid_values') as $valid_value) {
				fHTML::printOption(fGrammar::humanize($valid_value), $valid_value, $this->data['active_record']->$encode());
			} ?>
		</select>
	<? } ?>

	<? if ($info['type'] == 'textarea') { ?>

	<? } ?>

	<? if ($info['type'] == 'date') { ?>
		<input type="text" name="<?= $this->data['record'] ?>[<?= $column ?>]" value="<?= $this->data['active_record']->$encode('console_date') ?>" />
	<? } ?>

	<? if ($info['type'] == 'time') { ?>
		<input type="text" name="<?= $this->data['record'] ?>[<?= $column ?>]" value="<?= $this->data['active_record']->$encode('console_time') ?>" />
	<? } ?>

	<? if ($info['type'] == 'timestamp') { ?>
		<input type="text" name="<?= $this->data['record'] ?>[<?= $column ?>]" value="<?= $this->data['active_record']->$encode('console_timestamp') ?>" />
	<? } ?>


<? } ?>
</fieldset>
