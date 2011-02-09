<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
	<head>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

		<title><%= $this->rcombine('title') %></title>

		<!--[if IE]><![endif]-->

		<% $this->place('styles');  %>
		<% $this->place('scripts'); %>
	</head>
	<body id="<%= $this->pull('id') %>" class="<%= $this->combine('classes', ' ') %>">
		<% $this->place('header'); %>
		<div class="torso">
			<% $this->place('contents')   %>
		</div>
		<% $this->place('footer'); %>
	</body>
</html>
