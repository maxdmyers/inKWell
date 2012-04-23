<!DOCTYPE HTML>
<html>
	<head>
		<title>inKWell - A PHP MVC Framework for PHP Developers</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

		<script type="text/javascript">
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', 'UA-30892540-1']);
			_gaq.push(['_trackPageview']);

			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
			})();
		</script>

		<!-- Common -->

		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
		<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Bitter:400,700" type="text/css" media="screen" />
		<link rel="stylesheet" href="http://dotink.github.com/inKLing/inkling.css" type="text/css" media="screen" />

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
				$("a[rel^='prettyPhoto']").prettyPhoto();
			});
		</script>

		<style type="text/css">

			html {
				background-color: rgb(30,32,34);
			}

			body {
				color: #aaa;
				font-size: 1.6em;
			}

			body > header,
			body > .torso,
			body > footer {
				margin: auto;
			}


			body#home > .torso {
				min-width: 980px;
				max-width: 1396px;
			}

			body > .torso {
				min-width: 800px;
				max-width: 800px;
				padding: 0 15px;
			}

			body > header,
			body > footer {
				width: 100%;
				min-height: 200px;
			}

			body > header {
				background-image: linear-gradient(bottom, rgb(31,31,31) 33%, rgb(15,15,15) 67%);
				background-image: -o-linear-gradient(bottom, rgb(31,31,31) 33%, rgb(15,15,15) 67%);
				background-image: -moz-linear-gradient(bottom, rgb(31,31,31) 33%, rgb(15,15,15) 67%);
				background-image: -webkit-linear-gradient(bottom, rgb(31,31,31) 33%, rgb(15,15,15) 67%);
				background-image: -ms-linear-gradient(bottom, rgb(31,31,31) 33%, rgb(15,15,15) 67%);

				background-image: -webkit-gradient(
					linear,
					left bottom,
					left top,
					color-stop(0.33, rgb(31,31,31)),
					color-stop(0.67, rgb(15,15,15))
				);

				border-bottom: dotted 1px rgb(223,153,123);
				padding: 2em 0;
			}

			a {
				color: #abbacf;
				text-decoration: underline;
			}

			h2,h3,h4 {
				font-family: Bitter, sans-serif;
			}

			h2 {
				text-align: center;
			}

			h2 {
				font-size: 3.2em;
				font-weight: bold;
			}

			h3,h4 {
				color: rgb(230,230,210);
			}

			h5,h6 {
				color: rgb(200, 200, 180);
			}

			h4 {
				font-size: 1.4em;
			}

			h5 {
				font-size: 1.2em;
			}

			h6 {
				font-size: 1.1em;
			}

			header h1 {
				background: transparent center url('/assets/images/inkwell_logo.png');
				width: 500px;
				height: 245px;
				margin: auto;
				text-indent: -500px;
				overflow: hidden;
			}

			pre + h1,
			pre + h2,
			pre + h3,
			pre + h4,
			pre + h5,
			pre + h6 {
				margin-top: .5em;
			}

			ul > li {
				list-style-image: url(http://cdn.dustball.com/bullet_go.png);
			}

			strong {
				font-weight: bold;
			}

			figure {
				margin-bottom: 3em;
			}

			figcaption {
				background-color: rgb(225,225,225);
				border-radius: 0 0 .5em .5em;
				color: rgb(25,25,25);
				padding: 1em;
				text-align: center;
				border-top: solid 1px rgb(50,50,50);
			}

			code {
				color: #baccdd;
				font-size: 13px;
			}

			pre code {
				border-radius: .5em .5em .5em .5em;
				padding: 1% 2.5%;
				tab-size: 4;
				box-shadow: inset 0 0 1em rgb(80,80,80);
				-moz-tab-size: 4;
				margin-bottom: 2em;
			}

			figure pre code {
				border-radius: .5em .5em 0 0;
				margin-bottom: 0;
			}

			.featurette.group > div {
				width: 48%;
				padding: 0 1%;
			}

			.brochure.group > section {
				width: 30%;
				padding: 0 1.5%;
			}

			hr {
				margin-bottom: 1em;
			}

			ul a,
			ol a {
				text-decoration: none;
				color: #fff;
				font-size: .95em;
			}


			a.button {
				border-top: 1px solid #1f2224;
				background: #914165;
				background: -webkit-gradient(linear, left top, left bottom, from(#f296c7), to(#914165));
				background: -webkit-linear-gradient(top, #f296c7, #914165);
				background: -moz-linear-gradient(top, #f296c7, #914165);
				background: -ms-linear-gradient(top, #f296c7, #914165);
				background: -o-linear-gradient(top, #f296c7, #914165);
				padding: 10.5px 21px;
				-webkit-border-radius: 7px;
				-moz-border-radius: 7px;
				border-radius: 7px;
				-webkit-box-shadow: rgba(0,0,0,1) 0 1px 0;
				-moz-box-shadow: rgba(0,0,0,1) 0 1px 0;
				box-shadow: rgba(0,0,0,1) 0 1px 0;
				text-shadow: rgba(0,0,0,.4) 0 1px 0;
				color: #ffffff;
				font-size: 19px;
				font-family: Helvetica, Arial, Sans-Serif;
				text-decoration: none;
				vertical-align: middle;
			}
			a.button:hover {
				border-top-color: #f296c7;
				background: #f296c7;
				color: #ffffff;
			}
			a.button:active {
				border-top-color: #f296c7;
				background: #f296c7;
			}


			#disqus_thread {
				background-color: #333333;
				border-radius: 1em 1em 1em 1em;
				margin-bottom: 20px;
				margin-top: 2.5em;
				padding: 20px;
			}

			#disqus_thread ul,
			#disqus_thread li {
				list-style-type: none;
				list-style-image: none;
			}

		</style>
	</head>
	<body id="<%= $this->pull('id', NULL) %>">
		<header>
			<hgroup>
				<h1>inKWell</h1>
				<h2>A PHP MVC Framework for PHP Developers</h2>
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
			<% if ($this->get('comments')) { %>
				<% $this->place('comments') %>
			<% } %>
		</div>
		<footer>
		</footer>
	</body>
</html>
