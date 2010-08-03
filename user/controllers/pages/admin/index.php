<?
	AuthController::requireLoggedIn();

	$page = new PagesController();

	$page->view
		-> add  ('primary_section', 'pages/admin/home.php')
		-> pack ('id',              'home')
		-> render();
