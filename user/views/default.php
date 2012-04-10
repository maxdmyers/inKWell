<!DOCTYPE HTML>
<html>
	<head>
		<title>inKWell - A PHP MVC Framework for PHP Developers</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

		<script src="http://yandex.st/highlightjs/6.1/highlight.min.js" type="text/javascript"></script>
		<script type="text/javascript">
			hljs.initHighlightingOnLoad();
		</script>

		<link rel="stylesheet" href="http://fonts.googleapis.com/css?family=Bitter:400,700" type="text/css" media="screen" />
		<link rel="stylesheet" href="http://dotink.github.com/inKLing/inkling.css" type="text/css" media="screen" />
		<link rel="stylesheet" href="http://yandex.st/highlightjs/6.1/styles/zenburn.css" type="text/css" />

		<style type="text/css">

			html {
				background-color: rgb(30,32,34);
			}

			body {
				color: rgb(220,220,220);
			}

			body > header,
			body > .torso,
			body > footer {
				margin: auto;
			}

			body > .torso {
				min-width: 980px;
				max-width: 1396px;
				padding: 0 15px;
			}

			body > header,
			body > footer {
				width: 100%;
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
				color: rgb(155, 170, 175);
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
				color: rgb(250,250,230);
			}

			header h1 {
				background: transparent center url('/inkwell_logo.png');
				width: 500px;
				height: 245px;
				margin: auto;
				text-indent: -500px;
				overflow: hidden;
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

			pre code {
				border-radius: .5em .5em .5em .5em;
				padding: 1% 2.5%;
				tab-size: 4;
				box-shadow: inset 0 0 1em rgb(80,80,80);
				-moz-tab-size: 4;
			}

			figure pre code {
				border-radius: .5em .5em 0 0;
			}

			.featurette.group > div {
				width: 48%;
				padding: 0 1%;
			}

			.brochure.group > section {
				width: 30%;
				padding: 0 1.5%;
			}

		</style>
	</head>
	<body>
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
					<button type="submit" name="edit" value="">Edit</button>
				</form>
			<% } %>
		</div>
		<footer>
		</footer>
	</body>
</html>
