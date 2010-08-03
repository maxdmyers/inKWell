<?
	$page = new PagesController();

	$page->view
		-> add  ('primary_section', 'pages/default/home.php')
		-> pack ('id',              'home')
		-> push ('title',           'Welcome to inKWell!')
		-> render();
