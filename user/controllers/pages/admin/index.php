<?

	if (!User::checkLoggedIn()) {
		self::triggerError('not_authorized');
	}

	$page = new PagesController();

	$page->view
		-> add  ('primary_section', 'pages/admin/home.php')
		-> pack ('id',              'home')
		-> render();
