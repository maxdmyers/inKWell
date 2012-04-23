<!DOCTYPE HTML>
<html>
	<head>
		<title><%= $this->pull('title') %></title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

		<% if ($this->pull('ga_ua_id')) { %>
			<script type="text/javascript">
				var _gaq = _gaq || [];
				_gaq.push(['_setAccount', '<%= $this->pull('ga_ua_id') %>']);
				_gaq.push(['_trackPageview']);

				(function() {
					var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
					ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
					var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				})();
			</script>
		<% } %>

		<!-- Common -->

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
		<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Bitter:400,700" type="text/css" media="screen" />
		<link rel="stylesheet" href="http://dotink.github.com/inKLing/inkling.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="/assets/styles/simplewiki.css" type="text/css" media="screen" />

		<!-- Highlight.js -->

		<script src="http://yandex.st/highlightjs/6.1/highlight.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			hljs.initHighlightingOnLoad();
		</script>
		<link rel="stylesheet" href="http://yandex.st/highlightjs/6.1/styles/dark.css" type="text/css" />


		<!-- MarkItUp -->

		<script type="text/javascript" src="/assets/scripts/markitup/jquery.markitup.js"></script>
		<script type="text/javascript" src="/assets/scripts/markitup/sets/default/set.js"></script>
		<link rel="stylesheet" type="text/css" href="/assets/scripts/markitup/skins/markitup/style.css" />
		<link rel="stylesheet" type="text/css" href="/assets/scripts/markitup/sets/default/style.css" />

		<!-- prettyPhoto -->

		<script type="text/javascript" src="/assets/scripts/jquery.prettyPhoto.js"></script>
		<link rel="stylesheet" type="text/css" href="/assets/styles/prettyPhoto.css" />
		<script type="text/javascript" charset="utf-8">
			$(document).ready(function(){
				$("a[rel^='lightbox']").prettyPhoto();
			});
		</script>

		<style type="text/css">

		</style>
	</head>
	<body id="<%= $this->pull('id', NULL) %>">
		<header>
			<hgroup>
				<h1><%= $this->pull('title') %></h1>
			</hgroup>
		</header>
		<div class="torso">
			<% $this->place('content'); %>
			<% if (fSession::get('source')) { %>
				<form action="" method="post">
					<input type="hidden" name="source" value="<%= fHTML::encode(fSession::delete('source')) %>" />
					<button type="submit" name="action" value="save">Save</button>
					<button type="submit" name="action" value="edit">Edit</button>
				</form>
			<% } %>
			<% if ($this->pull('disqus_id', NULL)) { %>
				<% $this->place('comments') %>
			<% } %>
		</div>
		<footer>
		</footer>
	</body>
</html>
