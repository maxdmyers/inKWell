<?php

	return array(

		'disabled'               => FALSE,
		'scaffolding_root'       => 'scaffolding',

		'autoloaders'            => array(
			'**Controller'       => 'ARController::__make',
			'_active_records'    => 'ActiveRecord::__make',
			'_record_sets'       => 'RecordSet::__make'
		)
	);
