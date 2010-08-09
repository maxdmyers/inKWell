<div class="primary section">
	<h1><?= $this->peal('title') ?></h1>
	<h2>Introduction</h2>
	<p>
		inKWell is the <em>anti-convention</em> framework.  Our philosophy is
		that learning how to do everything you might normally do in PHP via
		a framework, vs. just doing it, should not really be opposing concepts.
		We think we achieve this philosophy pretty simply by creating a few
		key concepts which are simple to understand and easy to take hold of.
	</p>
	<h2>Everything is PHP</h2>
	<p>
		Everything in inKWell starting with configuration files all the way up
		to your views is 100% PHP.  We don't use YAML, we don't use Smarty, and
		we certainly don't use XML.  That said, you're completely welcome to use
		any of those things if you like.  There are plenty of ways to interface
		with all of those abstractions using PHP.
	</p>
	<h3>How do I create a new model instance in the inKWell console?</h3>
	<p>
		The same way you do anywhere else, using PHP:
	</p>
	<pre class="code php">
$user = new User();
	</pre>
	<h3>How do I tell inKWell where to load my custom classes from?</h3>
	<p>
		Using PHP of course!
	</p>
	<pre class="code php">
'geshi*' => 'includes/lib/geshi'
	</pre>
	<h3>And using part of the page title in my view?</h3>
	<p>
		We told you, EVERYTHING IS PHP!
	</p>
	<pre class="code php">
&lt;h1&gt;&lt;?= $this->peal('title') ?&gt;&lt;/h1&gt;
	</pre>
</div>
