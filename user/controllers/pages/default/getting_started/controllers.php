<?

	$page = new PagesController();

	$page->view
		-> add    ('primary_section',   'pages/default/getting_started/controllers.php')
		-> add    ('secondary_section', 'pages/default/aside.php')
//		-> digest ('blocks',            'secondary content goes here')
		-> pack   ('id',                'controllers')
		-> push   ('classes',           'getting_started')
		-> push   ('title',             'Creating Controllers')
		-> render ();

