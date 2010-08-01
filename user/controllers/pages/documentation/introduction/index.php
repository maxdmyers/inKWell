<h1>Introduction</h1>
<p>
	The inKWell framework and content management system is an <acronym title="Model/View/Controller">MVC</acronym> framework
	built primarily on Flourish (<a href="http://www.flourishlib.com">http://www.flourishlib.com</a>) and Moor (<a href="http://github.com/jeffturcotte/moor">http://github.com/jeffturcotte/moor</a>).
	Like other MVC frameworks it employs a default structure for creating and extending your site or web applications via the customization of its central
	three components (Models, Views, and Controllers).
</p>
<p>
	Since Flourish provides an extensive base class and library, inKWell is implemented using only a select few boostrap files, classes, and a utilities.
</p>
<h2>Bootstrapping</h2>
<ul>
	<li><a href="bootstrap/Configuration/index.php">config.php</a> - Centralized abstracted configuration information in an easy to read and edit format.</li>
	<li><a href="bootstrap/Initialization/index.php">init.php</a> - Responsible for setting up site wide initialization (Database connections, autoloading, etc).</li>
	<li><a href="bootstrap/Routing/index.hp">routing.php</a> - Nearly 100% responsible for directing user requests to the proper internal controllers.</li>
</ul>
<p>
	Bootstrap files are loaded in reverse order.  That is to say the routing file is the first to be hit, which in turn will load the init, which in turn will load the config.
	This is important to realize because any custom URL rewriting done on the server level may interfere with whether or not init and thus, config, is loaded.  <em>The suggested
	method is to handle <strong>ALL</strong> requests, with the exception of direct extensionless requests via routing.</em>  The suggested behavior is designed to promote
	clean but obfuscated URLs and to additionally push developers towards the MVC pattern.
</p>
<h2>Library</h2>
<ul>
	<li><a href="classes/AtiveRecord/index.php">ActiveRecord</a> - Responsible for representing objects that represent database records. (Model)</li>
	<li><a href="classes/RecordSet/index.php">RecordSet</a> - A pseudo-class responsible for representing a set of Active Records. (Model)</li>
	<li><a href="classes/Template/index.php">Template</a> - Responsible for templating. (View)</li>
	<li><a href="classes/Block/index.php">Block</a> - Responsible for representing isolated but common elements on a page (Controller/View)
	<li><a href="classes/Controller/index.php">Controller</a> - Responsible for directly interfacing with user requests and coordinating all other components. (Controller)</li>
	<li><a href="classes/Scaffolder/index.php">Scaffolder</a> - Responsible for code generation, rapid development, and reflection</li>
</ul>
<h2>Utilities</h2>
<ul>
	<li><a href="utilities/inKWell_Console/index.php">inKWell Console</a> - A simple PHP "shell" which intergrates with the inKWell environment.</li>
</ul>
