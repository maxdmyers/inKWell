<fieldset class="primary">
	<% foreach($this->pull('primary_columns') as $column => $info) {
		$method = 'encode' . fGrammar::camelize($column, TRUE);
		%>
		<label
			for="<%= $this->pull('record_name') %>-<%= $column %>">
				<%= fGrammar::humanize($column) %>
		</label>
		<% switch($info['format']) {
			case 'integer':
			case 'float': %>
					<input
						id="<%= $this->pull('record_name') %>-<%= $column %>"
						class="<%= $info['format'] %>"
						type="text"
						name="<%= $column %>"
						value="<%= $this->pull('active_record')->$method() %>"
					/>
				<%
				break;
			case 'string':
				if (($valid_values = $info['valid_values'])) { %>
					<select
						id="<%= $this->pull('record_name') %>-<%= $column %>"
						class="<%= $info['format'] %>"
						name="<%= $column %>"
					>
						<% foreach ($valid_values as $valid_value) { %>
							<option
								<%= ($valid_value == $this->pull('active_record')->$method())
									? 'selected="selected"'
									: ''
								%>
							><%= $valid_value %></option>
						<% } %>
					</select>
				<% } elseif ($info['max_length'] > 128) { %>
					<textarea
						id="<%= $this->pull('record_name') %>-<%= $column %>"
						class="<%= $info['format'] %>"
						name="<%= $column %>"
						cols="512"
						rows="<%= $info['max_length'] / 128 %>"
					><%= $this->pull('active_record')->$method() %></textarea>
				<% } else { %>
					<input
						id="<%= $this->pull('record_name') %>-<%= $column %>"
						type="text"
						class="<%= $info['format'] %>"
						maxlength="<%= $info['max_length'] %>"
						name="<%= $column %>"
						value="<%= $this->pull('active_record')->$method() %>"
					/>
				<% }
				break;
			case 'slug': %>
					<input
						id="<%= $this->pull('record_name') %>-<%= $column %>"
						class="<%= $info['format'] %>"
						type="text"
						name="<%= $column %>"
						value="<%= $this->pull('active_record')->$method() %>"
						maxlength="<%= $info['max_length'] %>"
					/>
					<p class="helper">
						A URL Slug is appeneded to a URL to identify a unique
						resource.  You can enter any valid URL characters.
					</p>
				<%
				break;
			case 'url':
			case 'email': %>
					<input
						id="<%= $this->pull('record_name') %>-<%= $column %>"
						class="<%= $info['format'] %>"
						type="text"
						name="<%= $column %>"
						value="<%= $this->pull('active_record')->$method() %>"
						maxlength="<%= $info['max_length'] %>"
					/>
				<%
				break;
			case 'text': %>
				<textarea
					id="<%= $this->pull('record_name') %>-<%= $column %>"
					class="<%= $info['format'] %>"
					name="<%= $column %>"
					cols="512"
					rows="8"
				><%= $this->pull('active_record')->$method() %></textarea>
				<%
				break;

			case 'password':
				%>
				<fieldset class="<%= $info['format'] %>">
					<% if ($this->pull('active_record')->$method()) { %>
						<p class="helper">
							Leave empty to keep the same password.
						</p>
					<% } %>

					<input
						id="<%= $this->pull('record_name') %>-<%= $column %>"
						class="<%= $info['format'] %>"
						type="password"
						name="<%= $column %>"
						value=""
						maxlength="<%= $info['max_length'] %>"
					/>
					<label
						for="confirm-<%= $this->pull('record_name') %>-<%= $column %>">
							Confirm <%= fGrammar::humanize($column) %>
					</label>
					<input
						id="confirm-<%= $this->pull('record_name') %>-<%= $column %>"
						class="<%= $info['format'] %>"
						type="password"
						name="confirm-<%= $column %>"
						value=""
						maxlength="<%= $info['max_length'] %>"
					/>
				</fieldset>
				<%
				break;
			case 'file':
				%>
				<input
					id="<%= $this->pull('record_name') %>-<%= $column %>"
					class="<%= $info['format'] %>"
					name="<%= $column %>"
					type="file"
				/>
				<% if (($existing = $this->pull('active_record')->$method())) {
					$method = 'prepare' . fGrammar::camelize($column, TRUE);
					%>
					<fieldset class="file">

						<a href="<%= $this->pull('active_record')->$method(TRUE) %>">
							Download <%= fGrammar::humanize($column) %>
						</a>
						<div>
							<input
								name="existing-<%= $column %>"
								value="<%= $existing %>"
								type="hidden"
							/>

							<input
								name="delete-<%= $column %>"
								value="0"
								type="hidden"
							/>
							<% if (!$info['not_null']) { %>
								<input
									id="delete-<%= $this->pull('record_name') %>-<%= $column %>"
									name="delete-<%= $column %>"
									value="1"
									type="checkbox"
								/>

								<label for="delete-<%= $this->pull('record_name') %>-<%= $column %>">
									Delete Existing <%= fGrammar::humanize($column) %>
								</label>
							<% } %>
						</div>
					</fieldset>
				<% }
				break;
			case 'image':
				%>
				<input
					id="<%= $this->pull('record_name') %>-<%= $column %>"
					class="<%= $info['format'] %>"
					type="file"
					name="<%= $column %>"
				/>
				<% if (($existing = $this->pull('active_record')->$method())) {
					$method = 'prepare' . fGrammar::camelize($column, TRUE);
					%>
					<fieldset class="image">

						<a href="#display-<%= $column %>">
							<img
								class="thumbnail"
								src="<%= $this->pull('active_record')->$method(TRUE) %>"
								alt="<%= fGrammar::humanize(
									$this->pull('active_record') . ' ' . $column
								) %>"
							/>
						</a>
						<div class="lightbox">
							<a id="display-<%= $column %>" href="#close_lightbox">
								<img
									src="<%= $this->pull('active_record')->$method(TRUE) %>"
									alt="<%= fGrammar::humanize(
										$this->pull('active_record') . ' ' . $column
									) %>"
								/>
							</a>
						</div>

						<div>
							<input
								type="hidden"
								name="existing-<%= $column %>"
								value="<%= $existing %>"
							/>

							<input
								type="hidden"
								name="delete-<%= $column %>"
								value="0"
							/>
							<% if (!$info['not_null']) { %>
								<input
									id="delete-<%= $this->pull('record_name') %>-<%= $column %>"
									type="checkbox"
									name="delete-<%= $column %>"
									value="1"
								/>

								<label for="delete-<%= $this->pull('record_name') %>-<%= $column %>">
									Delete Existing <%= fGrammar::humanize($column) %>
								</label>
							<% } %>
						</div>

					</fieldset>
				<% }
				break;

			case 'date':
			case 'time':
			case 'timestamp':
				if ($info['not_null'] && !$info['default']) { %>
					<input
						id="<%= $this->pull('record_name') %>-<%= $column %>"
						class="<%= $info['format'] %>"
						type="text"
						name="<%= $column %>"
						value="<%= $this->pull('active_record')->$method() %>"
					/>
				<% }
				break;

			case 'checkbox':
			case 'boolean':
				%>
				<input
					type="hidden"
					name="<%= $column %>"
					value="0"
				/>
				<input
					id="<%= $this->pull('record_name') %>-<%= $column %>"
					class="<%= $info['format'] %>"
					type="checkbox"
					name="<%= $column %>"
					value="1"
					<%= ($this->pull('active_record')->$method())
						? 'checked="checked"'
						: ''
					%>
				/>
				<%
				break;
		}
	} %>
</fieldset>
<fieldset class="related">

</fieldset>
