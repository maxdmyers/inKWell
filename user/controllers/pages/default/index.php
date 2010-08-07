<?
	$page = new PagesController();

	$page->view
		-> add    ('primary_section',   'pages/default/index.php')
		-> add    ('secondary_section', 'pages/default/aside.php')
		-> digest ('blocks',            'secondary content goes here')
		-> pack   ('id',                'home')
		-> push   ('title',             'Welcome to inKWell!')
		-> render ();
