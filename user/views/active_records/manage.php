<div class="primary section">
	<h2>
		<%= $this->pull('title') %>
		<% if (($related_record = $this->pull('related_record'))) { %>
			<span class="title"><%= $related_record %></span>
		<% } %>
	</h2>

	<% foreach ($message_types = array('alert', 'helper', 'error', 'success') as $message_type) {
		fMessaging::show($message_type, iw::makeTarget($this->pull('controller_class'), $this->pull('action')));
	} %>

	<% if ($this->pull('active_record_set')->count() || $this->pull('filter_column')) { %>

		<% if (count($this->pull('filter_columns'))) { %>
			<form action="" method="post" class="filter">
				<fieldset>
					<select name="filter_column">
						<option value="">Select Filter Column</option>
						<% foreach ($this->pull('filter_columns') as $filter) {
							fHTML::printOption(fGrammar::humanize($filter), $filter, $this->pull('filter_column'));
						} %>
					</select>
					<input name="filter_value"type="text" size="15" value="<%= fHTML::encode($this->pull('filter_value')) %>"/>
					<button type="submit">
						Go!
					</button>
					<p class="helper">
						You can use * as wildcards.
					</p>
				</fieldset>
			</form>
		<% } %>

		<form action="" method="post">
			<table class="record_set">
				<thead>
					<tr>
						<% if ($can_damage = User::checkACL($this->pull('entry'), array(PERM_REMOVE, PERM_UPDATE))) { %>
							<th class="selection">Selected</th>
						<% } %>
						<% foreach (array_keys($this->pull('display_columns')) as $display_column) { %>
							<% if (in_array($display_column, $this->pull('sortable_columns'))) { %>
								<th class="sortable">
									<%= fGrammar::humanize($display_column) %>
									<span class="directions">
										<a	class="<%= $this->selectOn(array('sort_column' => $display_column, 'sort_direction' => 'asc')) %>"
											href="?<%= http_build_query(array(
												'sort_column'    => $display_column,
												'sort_direction' => 'asc',
												'page'           => $this->pull('page')
											), '', '&amp') %>"
											title="Lower Values First" >
												
										</a>
										<a	class="<%= $this->selectOn(array('sort_column' => $display_column, 'sort_direction' => 'desc')) %>"
											href="?<%= http_build_query(array(
												'sort_column'    => $display_column,
												'sort_direction' => 'desc',
												'page'           => $this->pull('page')
											), '', '&amp') %>"
											title="Higher Values First" >
												
										</a>
									</span>
								</th>
							<% } else { %>
								<th>
									<%= fGrammar::humanize($display_column) %>
								</th>
							<% } %>
						<% } %>
						<% if ($can_damage) { %>
							<th class="last">Available Actions (<a href="create">Add</a>)</th>
						<% } %>
					</tr>
				</thead>
				<tfoot>
					<tr>
						<% if ($can_damage) { %>
							<td colspan="<%= sizeof($this->pull('display_columns')) + 1 %>"><em>With Selected:</em></td>
							<td>
								<input type="hidden" name="action" value="update" />
								<button
									type="submit"
									name="action"
									value="remove"
									<%= (!User::checkACL($this->pull('entry'), PERM_REMOVE)) ? 'disabled="disabled"' : '' %>
								>Remove</button>
							</td>
						<% } else { %>
							<td colspan="<%= sizeof($this->pull('display_columns')) %>">
								<em>You do not have the required permissions to remove and/or update these records.</em>
							</td>
						<% } %>
					</tr>
				</tfoot>
				<tbody>
					<% foreach($this->pull('active_record_set') as $position => $record) { %>
							<tr class="<%= $this->positionIn('active_record_set', $record) %>
							       <%= $this->highlightOn(array('affected_records' => $record->makeResourceKey(FALSE))) %>"
							>
							<% if ($can_damage) { %>
								<td class="selection">
									<input
										type="checkbox"
										name="<%= $this->pull('entry') %>[]"
										value="<%= fHTML::encode($record->makeResourceKey(FALSE)) %>"
									/>
								</td>
							<% } %>
							<% foreach ($this->pull('display_columns') as $display_column => $type) {
								$method = 'prepare' . fGrammar::camelize($display_column, TRUE);
								switch ($type) {
									case 'date':
										%><td class="<%= $display_column %>"><%= $record->$method('console_date') %></td><%
										break;
									case 'time':
										%><td class="<%= $display_column %>"><%= $record->$method('console_time') %></td><%
										break;
									case 'timestamp'
										%><td class="<%= $display_column %>"><%= $record->$method('console_timestamp') %></td><%
										break;
									case 'image':
										%><td class="<%= $display_column %>"><%
											if ($image_url = $record->$method(TRUE)) { %>
												<a href="#<%= $image_id = $display_column . ':' . $record->makeSlug(FALSE) %>">View</a>
												<div class="lightbox">
													<a id="<%= $image_id %>" href="#close_lightbox">
														<img src="<%= $image_url %>" alt="<%= fGrammar::humanize($display_column) %> for <%= $record %>"/>
													</a>
												</div>
											<% } else { %>
												<em>Not Available</em>
											<% }
										%></td><%
										break;
									case 'file':
										%><td class="<%= $display_column %>"><%
											if ($file_url = $record->$method(TRUE)) { %>
												<a href="<%= $file_url %>">Download</a>
											<% } else { %>
												<em>Not Available</em>
											<% }
										%></td><%
										break;
									case 'ordering':
										%><td class="ordering <%= $display_column %>"><%
											if ($this->pull('active_record_set')->count(TRUE) === 1) {
												%><em>Not Enough Records</em><%
											} else {
												if ($this->pull('page') != $this->pull('page_count') || ($position + 1) != $this->pull('active_record_set')->count()) {
													%><button
														title="Lower Priority"
														type="submit"
														name="<%= $this->pull('entry') %>[<%= fHTML::encode($record->makeResourceKey(FALSE)) %>][<%= $display_column %>]"
														value="<%= $record->$method() + 1 %>">⬇</button><%
												}
												if ($this->pull('page') != 1 || $position > 0) {
													%><button
														title="Higher Priority"
														type="submit"
														name="<%= $this->pull('entry') %>[<%= fHTML::encode($record->makeResourceKey(FALSE)) %>][<%= $display_column %>]"
														value="<%= $record->$method() - 1 %>">⬆</button><%
												}
											}
										%></td><%
										break;
									case 'url':
										%><td class="url <%= $display_column %>"><%
											if ($url = $record->$method()) { %>
												<a href="<%= $url %>">Visit</a>
											<% } else { %>
												<em>Not Available</em>
											<% }
										%></td><%
										break;
									case 'email':
										%><td class="email <%= $display_column %>"><%
											if ($email = $record->$method()) { %>
												<a href="mailto:<%= $email %>"><%= $email %></a>
											<% } else { %>
												<em>Not Available</em>
											<% }
										%></td><%
										break;
									default:
										%><td class="<%= $display_column %>"><%= $record->$method() %></td><%
										break;
								}
							} %>
							<% if ($can_damage) { %>
								<td class="last">
									<% if (User::checkACL($this->pull('entry'), PERM_UPDATE)) { %>
										<% if ($this->pull('related_record')) { %>
											<a href="<%= $record->makeSlug(FALSE) . '/update' %>">
												Update
											</a>
										<% } else { %>
											<a href="<%= Moor::linkTo($this->pull('controller_class') . '::update :pkey', $record->makeSlug(FALSE)) %>">
												Update
											</a>
										<% } %>
									<% } %>
									<% foreach ($this->pull('child_entries') as $child_entry => $controller_class) { %>
										|
										<a href="<%= Moor::linkTo($controller_class . '::manage :related_pkey :related_entry', $record->makeSlug(FALSE), $this->pull('entry')) %>">
											Manage <%= fGrammar::humanize($child_entry) %>
										</a>
									<% } %>
								</td>
							<% } %>
						</tr>
					<% } %>
				</tbody>
			</table>
		</form>
	<% } %>
	<% if ($this->pull('page_count') > 1) { %>
		<div class="pages">
			Pages:
			<% for ($x = 1; $x <= $this->pull('page_count'); $x++) { %>
				<a class="<%= $this->selectOn(array('page' => $x)) %>" href="<%= fURL::replaceInQueryString('page', $x) %>"><%= $x %></a>
			<% } %>
		</div>
	<% } %>
</div>
