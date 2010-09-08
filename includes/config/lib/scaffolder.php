<?php

	return array(

		'disabled'            => FALSE,
		'scaffolding_root'    => 'scaffolding',

		// The scaffolder output_map is an associative array of classes to
		// output directories.  If the scaffolder is enabled, the classes will
		// Also be appeneded to the inKWell autoloader for on-the-fly
		// scaffolding.
		//
		// All classes are expected to have a method equal to the current
		// Scaffolder::DYNAMIC_SCAFFOLD_METHOD in addition to the
		// iw::MATCH_CLASS_METHOD.

		'output_map'          => array(
			'ActiveRecord'    => 'user/models',
			'RecordSet'       => 'user/models/sets'
		)

	);
