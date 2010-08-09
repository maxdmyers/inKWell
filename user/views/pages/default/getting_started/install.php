<div class="primary section">
	<h1><?= $this->peal('title') ?></h1>
	<h2>Introduction</h2>
	<p>
		Using inKWell can be as simple or as complex as you want and or need
		it to be.  The core framework is comprised of <em>only six classes</em>,
		although most users will want to install the
		<a href="/extensions/">official extensions</a>.
	</p>
	<p>
		Initial installation will depend on which extensions you install, so
		for now we will focus on installing only the core.  This will allow you
		to begin creating controllers, models, and views without having to
		configure each little separate piece.  This is ideal if you don't plan
		to use any of the official extensions, or even if you plan on
		installing them at a later point.
	</p>
	<h2>Requirements</h2>
	<ul>
		<li>PHP 5.1.6 or greater.</li>
		<li>Apache 1.3 or later with .htaccess support and mod-rewrite.</li>
	</ul>
	<h2>Downloading</h2>
	<p>
		The latest release is always available from
		<a href="http://inkwell.dotink.org/download">
			http://inkwell.dotink.org/download
		</a>.  You can simply click this and save it where you like or if you
		have shell access on your server, wget it.  If you require an earlier
		release or would like to check out development releases, you can visit
		<a href="/releases">our releases page</a>.
	</p>
	<h2>Installation</h2>
	<p>
		The core installation is a three step process.
	</p>
	<ol>
		<li>
			Copy all the files and folders from the release package into your
			empty document root on your server.
		</li>
		<li>
			Open and edit the file located at
			<code>includes/config/inkwell.php</code>.  Don't worry, this file
			is 100% php and contains comments for everything you need to know.
		</li>
		<li>
			Create your write directory (default 'writable') at whatever
			location you specify in this config file and give it the appropriate
			permissions so your webserver/php install can read and write to it.
		</li>
	</ol>
	<p>
		That's it!  You can actually make it a three step process if you decide
		not to change the default write directory.
	</p>
	<h2>Setup</h2>
	<h3>Database Support</h3>
	<p>
		If you would like to add database support to your inKWell install you
		can edit the file <code>includes/config/database.php</code>.  Database
		support is not required if you are simply going to be writing basic
		controllers and views.  Making use of models requires database support,
		however, as inKWell has no concept of a model without a schema.
	</p>
	<p>
		Because inKWell uses <a href="http://www.flourishlib.com">Flourish</a>
		for it's backend, often times configuration options and the API mimick
		Flourish's library API.  Currently Flourish and, thus, inKWell support
		the following database types:
	</p>
	<ul>
		<li>db2 (IBM's Enterprise level database)</li>
		<li>mssql (Microsoft's SQL offering)</li>
		<li>mysql (The most popular open source database)</li>
		<li>oracle (Now owns MySQL, but this is their highly commercial product)</li>
		<li>postgresql (A robust, clean, and extensible database)</li>
		<li>sqlite (Quick, easy, lightweight single file database)</li>
	</ul>
	<h3>Third-Party Dependencies</h3>
	<p>
		inKWell doesn't provide everything.  With that in mind, we do provide
		a simple way for you to extend and access existing third-party libraries
		you may use or wish to use on your site.  The inKWell auto-loader allows
		you to easily work with third-party dependencies by loading them from
		wherever you wish based only on certain conditions.
	</p>
	<p>
		The autoloader configuration, located by default in
		<code>includes/config/autoloaders.php</code> provides you with a simple
		way to set up your third party dependencies for autoloading, or if you
		like, extend them a bit using inKWell.
	</p>
	<p>
		You can simply drop them into your existing site structure and add a
		new entry.  Autoloader entries follow a $test => $target paradigm, i.e.
		they are elements of an associative array, but more importantly this
		means you can set up conditions for auto-loading from certain
		directories.  For example, if you installed something
		like <a href="http://qbnz.com/highlighter">GeSHi</a> and wanted to keep
		it somewhat self contained, you could add an entry as such:
	</p>
	<pre class="code php">
'geshi*' => 'includes/lib/geshi'
	</pre>
	<p>
		This would ensure that any class beginning with the term "geshi" would
		be loaded from the folder <code>includes/lib/geshi</code>.  Furthermore
		it would ensure that other classes which don't match, don't even attempt
		to load from that directory.
	</p>
</div>
