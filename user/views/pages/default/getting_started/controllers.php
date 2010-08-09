<div class="primary section">
	<h1><?= $this->peal('title') ?></h1>
	<p>
		There are at least two components to every page or resource that you
		access in inKWell.  The first of these is a controller, and the second
		is a view.  Creating a controller is fairly simple as it really is
		whatever you wish it to be.  The key concept to remember is simply that
		a controller is designed to handle all the logic required to complete
		the most simple request or the most complex series of requests.
		Some of the most common logic in controllers might be as follows:
	</p>
	<ul>
		<li>Determining which view should be shown to the user</li>
		<li>Gathering and organizing information to present in the view</li>
		<li>Redirecting users or triggering errors based on failures</li>
	</ul>
	<p>
		With that said, there are a few different philosophies on controllers.
		The first is that the number of controllers should be limited and the
		actions centralized.  We will call this the monolithic controller
		approach.  The second form is one in which multiple controllers may be
		instantiated, and multiple actions may be called.  We will call this
		the composite approach.
	</p>
	<h3>Creating a Monolithic Controller</h3>
	<p>
		Monolithic controllers offer a lot of benefits.  They can prevent
		needless code repetition, enforce strong convention, and ensure less
		conflicts.  A monolithic controller is generally a single class which
		has a number of methods representing each resource.  Let's take a look
		at what one of these might look like:
	</p>
	<pre class="code php">
class PagesController extends Controller
{

	static private $pagesPath = NULL;

	protected function prepare()
	{
		$this->view
			-> load ('html.php')
			-> set  ('header', self::$pagesPath . 'header.php')
			-> set  ('footer', self::$pagesPath . 'footer.php')
			-> push ('title',  '.inK');
	}

	static public function __init($config)
	{
		self::$pagesPath = implode(DIRECTORY_SEPARATOR, array(
			$_SERVER['DOCUMENT_ROOT'],  // Document Root
			'pages',                    // Sub Directory
			NULL                        // Trailing Slash
		));
	}

	static public function loadPage()
	{
		$clean_url = trim($_SERVER['REQUEST_URI'], '/');

		if ($clean_url) {
			$action = str_replace('/', '_', $clean_url);
		} else {
			$action = 'home';
		}

		$target = iw::makeTarget(__CLASS__, $action);

		if (!file_exists($view) {
			self::triggerError('not_found');
		}

		$controller->view
			-> set  ('primary_section', $view)
			-> pack ('id',              $action);

		if (method_exists($target)) {
			call_user_func($target, $controller);
		}

		$controller->view->render();

	}

	static public function home($controller)
	{
		$controller->view->push('title', 'Welcome Home');
	}

	static public function about($controller)
	{
		$controller->view->push('title', 'About Us');
	}

	static public function about_contact($controller)
	{
		$controller->view->push('title', 'Contact Us');
	}
}
	</pre>
	<p>
		As you can see in the above example, the core controller logic in the
		<code>::loadPage()</code> method provides for determining an appropriate
		view, page id, and is also responsible for rendering the view.  Each
		action on the controller provides only whatever particular customization
		is required for that page.
	</p>
	<h3>Composite Controllers and the official extension, PagesController()</h3>
	<p>
		Unlike the above example, the official inKWell PagesController extension
		uses a composite approach.  While it still provides a functional
		equivalent to <code>loadPage()</code>, it delegates control to a single
		file matching the request URI.  This means that the file itself becomes
		the controller action, but more importantly is responsible for all
		the requisite logic including creating the controller and rendering
		the view.
	</p>
	<p>
		To illustrate this, let's examine the controller which is used to build
		this page, which on our server is located at
		<code>
			user/controllers/pages/default/getting_started/controllers.php
		</code>:
	</p>
	<pre class="code php">
$page = new PagesController();

$page->view
	-> add    ('primary_section',   'pages/default/getting_started/controllers.php')
	-> add    ('secondary_section', 'pages/default/aside.php')
	-> pack   ('id',                'controllers')
	-> push   ('classes',           'getting_started')
	-> push   ('title',             'Creating Controllers')
	-> render ();
	</pre>
	<p>
		In this example our controller class <code>PagesController</code> does
		not actually determine our view or render it, but instead simply looks
		for a file matching the request URI to delegate control to.  Within
		that file it is hypothetically possible for us to instantiate any sort
		of controller we want, we can choose to render it, and with what view,
		etc.
	</p>
</div>
