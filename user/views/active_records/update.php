<div class="primary section">
	<h2>
		<%= $this->pull('title') %>
	</h2>

	<% foreach ($message_types = array('alert', 'helper', 'error', 'success') as $message_type) {
		fMessaging::show($message_type, iw::makeTarget($this->pull('controller_class'), $this->pull('action')));
	} %>

	<form action="" class="group" method="post" enctype="multipart/form-data">
		<% include 'fieldsets.php'; %>
		<fieldset class="actions">
			<input type="hidden" name="auth_token" value="<%= fRequest::generateCSRFToken() %>" />
			<button type="submit"><%= fGrammar::humanize($this->pull('action')) %></button>
		</fieldset>
	</form>
</div>
