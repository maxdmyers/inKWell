<?

	$page = new PagesController();

	$page->view
		-> add    ('primary_section',   'pages/default/getting_started/key_concepts.php')
		-> add    ('secondary_section', 'pages/default/aside.php')
		-> digest ('blocks',            'secondary content goes here')
		-> push   ('id',                'key_concepts')
		-> push   ('classes',           'getting_started')
		-> push   ('title',             'Key Concepts')
		-> render ();

