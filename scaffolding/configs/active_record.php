	return iw::createConfig('ActiveRecord', array(
		//
		// The database which holds the table for this active record model.  This is the database
		// alias name as configured in the 'databases' keys of the database.php not the 'name' key
		// or actual name of the database.
		//
		'database' => NULL,
		//
		// The table which maps to the active record, i.e., each instance of this class represents
		// a row on this table.
		//
		'table' => NULL,
		//
		// The humanized name of the record.  For a table such as 'email_addresses' you would want
		// to have something like 'Email Address'.  This value will default to the result of
		// fGrammar::humanize(<class name>)
		//
		'name' => NULL,
		//
		// The column which can naturally identify a record.  This column does not have to be
		// strictly unique, usually names, titles, or similar columns make good ID columns.
		//
		'id_column' => NULL,
		//
		// The column which stores identifiable slug for a record.  This column must have a unique
		// constraint and it's value must always be "url friendly."  If an ID column is set and
		// slug column values are not set manually, they will be the result of
		// fURL::makeFriendly(<id column value>)
		//
		'slug_column' => NULL,
		//
		// The order in which records should be sorted by default when added to a recordset.  This
		// is an array of keys (columns) to values 'desc' (descending) or 'asc' (ascending) and is
		// equivalent to the third parameter of fRecordSet::build()
		//
		'order' => NULL,
		//
		// EXTENDED COLUMN TYPES:
		//
		// Each of these is configured as an array of columns who should have relevant validation
		// logic attached as well as "smarter" data type returned, i.e. a member of the
		// 'image_columns' array should have it's value auto-instantiated as an fImage
		//
		'email_columns'    => array(),
		'url_columns'      => array(),
		'password_columns' => array(),
		'image_columns'    => array(),
		'upload_columns'   => array(),
		'order_columns'    => array(),
		'money_columns'    => array()
	));
