<div class="primary section">
	<h2><?= $this->data['title'] ?></h2>

	<? foreach ($message_types = array('alert', 'helper', 'error', 'success') as $message_type) {
		fMessaging::show($message_type, iw::makeTarget($this->data['controller_class'], $this->data['action']));
	} ?>

	<? if ($this->data['active_record_set']->count() || $this->data['filter_column']) { ?>

		<? if (count($this->data['filter_columns'])) { ?>
			<form action="" method="post" class="filter">
				<fieldset>
					<select name="filter_column">
						<option value="">Select Filter Column</option>
						<? foreach ($this->data['filter_columns'] as $filter) {
							fHTML::printOption(fGrammar::humanize($filter), $filter, $this->data['filter_column']);
						} ?>
					</select>
					<input name="filter_value"type="text" size="15" value="<?= fHTML::encode($this->data['filter_value']) ?>"/>
					<button type="submit">
						Go!
					</button>
					<p class="helper">
						You can use * as wildcards.
					</p>
				</fieldset>
			</form>
		<? } ?>

		<form action="" method="post">
			<table class="record_set">
				<thead>
					<tr>
						<? if ($can_damage = User::checkACL($this->data['entry'], array(PERM_REMOVE, PERM_UPDATE))) { ?>
							<th class="selection">Selected</th>
						<? } ?>
						<? foreach (array_keys($this->data['display_columns']) as $display_column) { ?>
							<th class="sortable">
								<?= fGrammar::humanize($display_column) ?>
								<? if (in_array($display_column, $this->data['sortable_columns'])) { ?>
									<span class="directions">
										<a	class="<?= $this->selectOn(array('sort_column' => $display_column, 'sort_direction' => 'asc')) ?>"
											href="?sort_column=<?= $display_column ?>&amp;sort_direction=asc">
												&darr;
										</a>
										<a	class="<?= $this->selectOn(array('sort_column' => $display_column, 'sort_direction' => 'desc')) ?>"
											href="?sort_column=<?= $display_column ?>&amp;sort_direction=desc">
												&uarr;
										</a>
									</span>
								<? } ?>
							</th>

						<? } ?>
						<? if ($can_damage) { ?>
							<th class="last">Available Actions</th>
						<? } ?>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<? if ($can_damage) { ?>
							<td colspan="<?= sizeof($this->data['display_columns']) + 1 ?>"><em>With Selected:</em></td>
							<td>
								<button name="action" value="remove" <?= (!User::checkACL($this->data['entry'], PERM_REMOVE)) ? 'disabled="disabled"' : '' ?>>Remove</button>
							</td>
						<? } else { ?>
							<td colspan="<?= sizeof($this->data['display_columns']) ?>">
								<em>You do not have the required permissions to remove and/or update these records.</em>
							</td>
						<? } ?>
					</tr>
				</tfoot>
				<tbody>
					<? foreach($this->data['active_record_set'] as $record) { ?>
						<tr class="<?= $this->positionIn('active_record_set', $record) ?> <?= $this->highlightOn(array('affected_records' => $record->makeSlug(FALSE))) ?>">
							<? if ($can_damage) { ?>
								<td class="selection"><input type="checkbox" name="<?= $this->data['entry'] ?>[]" value="<?= $record->makeSlug(FALSE) ?>" /></td>
							<? } ?>
							<? foreach ($this->data['display_columns'] as $display_column => $type) {
								$method = 'prepare' . fGrammar::camelize($display_column, TRUE);
								switch ($type) {
									case 'date':
										?><td class="<?= $display_column ?>"><?= $record->$method('console_date') ?></td><?
										break;
									case 'time':
										?><td class="<?= $display_column ?>"><?= $record->$method('console_time') ?></td><?
										break;
									case 'timestamp'
										?><td class="<?= $display_column ?>"><?= $record->$method('console_timestamp') ?></td><?
										break;
									case 'image':
										?><td class="<?= $display_column ?>"><?
											if ($image_url = $record->$method(TRUE)) { ?>
												<a href="#<?= $image_id = $display_column . ':' . $record->makeSlug(FALSE) ?>">View</a>
												<div class="lightbox">
													<a id="<?= $image_id ?>" href="#close_lightbox">
														<img src="<?= $image_url ?>" alt="<?= fGrammar::humanize($display_column) ?> for <?= $record ?>"/>
													</a>
												</div>
											<? } else { ?>
												<em>Not Available</em>
											<? }
										?></td><?
										break;
									case 'file':
										?><td class="<?= $display_column ?>"><?
											if ($file_url = $record->$method(TRUE)) { ?>
												<a href="<?= $file_url ?>">Download</a>
											<? } else { ?>
												<em>Not Available</em>
											<? }
										?></td><?
										break;
									default:
										?><td class="<?= $display_column ?>"><?= $record->$method() ?></td><?
										break;
								}
							} ?>
							<? if ($can_damage) { ?>
								<td class="last">
									<? if (User::checkACL($this->data['entry'], PERM_UPDATE)) { ?>
										<a href="<?= Moor::linkTo($this->data['controller_class'] . '::update :pkey', $record->makeSlug(FALSE)) ?>">Update</a>
									<? } ?>
								</td>
							<? } ?>
						</tr>
					<? } ?>
				</tbody>
			</table>
		</form>
	<? } ?>
</div>
