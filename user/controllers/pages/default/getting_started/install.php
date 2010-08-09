<?

	$page = new PagesController();

	$page->view
		-> add    ('primary_section',   'pages/default/getting_started/install.php')
		-> add    ('secondary_section', 'pages/default/aside.php')
		-> digest ('blocks',            'secondary content goes here')
		-> push   ('id',                'install')
		-> push   ('classes',           'getting_started')
		-> push   ('title',             'Installation and Setup')
		-> render ();

