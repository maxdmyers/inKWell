<div class="header group">
	<span class="logo"><?= $this->peal('title') ?></span>
	<span class="tagline">Stop Learning Frameworks.  Start Using PHP.</span>
</div>

<ul class="primary nav">
	<li><a href="/" class="<?= $this->selectOn(array('id' => 'home')) ?>">Start Over</a></li>
	<li class="<?= $this->selectOn(array('classes' => 'getting_started')) ?>">
		<a href="/getting_started/install">Getting Started</a>
		<ul class="secondary nav">
			<li class="<?= $this->selectOn(array('id' => 'install', 'classes' => 'getting_started')) ?>">
				<a href="/getting_started/install">Installation and Setup</a>
			</li>
			<!--
			<li class="<?= $this->selectOn(array('id' => 'key_concepts', 'classes' => 'getting_started')) ?>">
				<a href="/getting_started/key_concepts">Key Concepts</a>
			</li>
			-->
			<li class="<?= $this->selectOn(array('id' => 'controllers', 'classes' => 'getting_started')) ?>">
				<a href="/getting_started/controllers">Creating Controllers</a>
			</li>
		</ul>
	</li>
	<li><a href="/documentation/">Documentation and API</a></li>
	<li class="download">
		<a href="/download">
			Latest Release
			<span>Download</span>
		</a>
	</li>
</ul>
