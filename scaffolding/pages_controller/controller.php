	$page = new <%= self::validateVariable($build_class) %>();

	$page->view
		-> add   ('styles',      NULL)
		-> add   ('scripts',     NULL)
		-> add   ('contents',    '<%= $path %>')
		-> pack  ('id',          '<%= fURL::makeFriendly(pathinfo($path, PATHINFO_FILENAME)) %>')
		-> pack  ('description', 'Description of Page')
		-> push  ('title',       '<%= fGrammar::humanize(pathinfo($path, PATHINFO_FILENAME)) %> Page')
		-> render();
