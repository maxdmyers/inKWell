<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<title><%= $this->rcombine('title') %></title>
		<% $this->compress('styles');  %>
		<% $this->compress('scripts'); %>
	</head>
	<body id="<%= $this->pull('id') %>" class="<%= $this->combine('classes', ' ') %>">
		<% $this->place('header'); %>
		<div class="torso">
			<% $this->place('primary_section')   %>
			<% $this->place('secondary_section') %>
			<% $this->place('tertiary_section')  %>
		</div>
		<% $this->place('footer'); %>
	</body>
</html>
