<!DOCTYPE HTML>
<html>
	<head>
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
				background: transparent center url('inkwell_logo.png');
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
			<div class="featurette group">
				<div>
					<section>
						<h3>
							Installation
						</h3>
						<pre><code>
cd $DOCUMENT_ROOT
git clone --recursive git://github.com/dotink/inKWell.git ./
						</code></pre>
					</section>
					<section>
						<h3>A Quick Introduction</h3>
						<p>
							inKWell is a battle-tested beta framework built on <a href="#">wbond</a>'s <a href="#">Flourish library</a> and <a href="#">jeffturcotte</a>'s <a href="#">Moor</a> router.  It began in mid-2010 as a solution for the rapid development, easy maintenance, and modular extensibility required for freelance positions.
						</p>
						<p>
							Over the past two years it has grown into a stable and robust framework which abandons the cargo-cult mentality of existing MVC and HMVC frameworks, favoring configurability and developer accessibility over hyped up design and development patterns.
						</p>
						<p>
							The result is <em>fast</em>, <em>flexible</em>, and most importantly <em><strong>approachable</strong></em>!
						</p>
					</section>
					<div class="brochure group">
						<section>
							<h4>Getting Started</h4>
							<ul>
								<li>
									Configuration
								</li>
								<li>
									Routing
								</li>
								<li>
									Scaffolding
								</li>
								<li>
									Known Issues
								</li>
							</ul>
						</section>
						<section>
							<h4>Documentation</h4>
							<ul>
								<li>
									Creating a Controller
								</li>
								<li>
									Handling Common HTTP Headers
								</li>
								<li>
									Displaying a View
								</li>
								<li>
									Working with View Data
								</li>
								<li>
									Configuring a Database
								</li>
								<li>
									Using Models and Recordsets
								</li>
							</ul>
						</section>
						<section>
							<h4>Architecture</h4>
							<ul>
								<li>
									Autoloading
								</li>
								<li>
									Peer Controllers
								</li>
								<li>
									The inKWell Interface
								</li>
							</ul>
						</section>
					</div>
					<section>
						<h3>
							Frequently Asked Questions
						</h3>
						<ol>
							<li>
								How can I get my applciation files out of the document root?
							</li>
							<li>
								Why are my URLs preceded with /index.php?
							</li>
							<li>
								Can I load third-party classes easily?
							</li>
							<li>
								How can I run a cron script in the context of inKWell?
							</li>
							<li>
								Does inKWell support PHP 5.3 Namespaces?
							</li>
						</ol>
				</div>
				<div>
					<h3>Samples</h3>

					<figure>
						<pre><code>
return iw::createConfig('ActiveRecord', array(

	'table'     => 'auth.users',
	'id_column' => 'username',

	'password_columns' => array(
		'login_password'
	),

	'fixed_columns' => array(
		'date_created',
		'date_last_accessed',
		'last_acccessed_from'
	),

	'order' => array(
		'id' => 'asc'
	),
));
							</code></pre>
						<figcaption>
							A configuration for a user model using all standard configuration keys.
						</figcaption>
					</figure>

					<figure>
						<pre><code>
class UsersController extends Controller
{
	/**
	 * Gets a list user
	 */
	static public function list($page = 1, $limit = 15)
	{
		$page   = fRequest::get('page', 'integer', $page);
		$limit  = fRequest::get('limit', 'integer', $limit);
		$offset = ($page - 1) * $limit;

		$filter = array('active=' => TRUE);
		$order  = array('last_login_date' => 'desc');

		$users  = Users::build($filter, $order, $limit, $offset);

		$view   = View::create('users/list.php', array(
			'title' => 'Active Users',
			'page'  => $page,
			'users' => $users
		));

		switch (self::acceptTypes()) {
			case 'text/html':
				return $view;
			case 'application/json':
				return fJSON::encode($view);
			default:
				self::triggerError('not_acceptable');
		}
	}
}
						</code></pre>
						<figcaption>
							A simple controller with a single list method
						</figcaption>
					</figure>


					<figure>
						<pre><code>
&lt;h1>
	<%= $this->pull('title') %> (Page <%= $this->pull('page') %>)
&lt;/h1>
&lt;ul>
	<% $this->repeat('users', function($user, $view) { %>
		&lt;li>
			&lt;h2>
				<%= (string) $user %>
			&lt;/h2>
			&lt;p>
				This account was created on:
				<%= $user->prepareDateCreated('F jS, Y') %>
			&lt;/p>
		&lt;/li>
	<% }); %>
&lt;/ul>
						</code></pre>
						<figcaption>
							A view using the repeat method with a PHP 5.3 anonymous function for an emitter
						</figcaption>
					</figure>



				</div>
			</div>
		</div>
		<footer>
		</footer>
	</body>
</html>