<?php

	return array(

		'disabled'            => FALSE,
		'scaffolding_root'    => 'scaffolding',

		// Additional autoloaders can be configured to scaffold classes
		// on the fly.  These will be appended to the standard auto loader
		// array if scaffolding is enabled.  Please be aware that scaffolder
		// autoloaders share keys with the standard autoloaders

		'autoloaders'         => array(
			'**Controller'    => 'ARController::__make',
			'_active_records' => 'ActiveRecord::__make',
			'_record_sets'    => 'RecordSet::__make'
		)
	);
