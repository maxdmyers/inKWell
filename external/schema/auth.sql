CREATE SCHEMA auth;

CREATE TABLE auth.users (
	id serial PRIMARY KEY,
	username varchar(64) NOT NULL UNIQUE,
	login_password varchar(512) NOT NULL,
	avatar varchar(512) DEFAULT NULL,
	status varchar(16) NOT NULL DEFAULT 'Active' CHECK(status IN('Active', 'Inactive', 'Disabled')),
	date_created timestamp DEFAULT CURRENT_TIMESTAMP,
	date_last_accessed timestamp DEFAULT NULL
);

CREATE TABLE auth.roles (
	id serial PRIMARY KEY,
	name varchar(32) UNIQUE NOT NULL
);

CREATE TABLE auth.actions (
	id serial PRIMARY KEY,
	name varchar(32) UNIQUE NOT NULL,
	bit_value int UNIQUE NOT NULL
);

CREATE TABLE auth.user_roles (
	user_id int REFERENCES auth.users(id) ON DELETE CASCADE ON UPDATE CASCADE,
	role_id int REFERENCES auth.roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (user_id, role_id)
);

CREATE TABLE auth.user_permissions (
	user_id int REFERENCES auth.users(id) ON DELETE CASCADE ON UPDATE CASCADE,
	resource_key varchar(2048) NOT NULL,
	bit_value int NOT NULL,
	PRIMARY KEY (user_id, resource_key)
);

CREATE TABLE auth.role_permissions (
	role_id int REFERENCES auth.roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
	resource_key varchar(2048) NOT NULL,
	bit_value int NOT NULL,
	PRIMARY KEY (role_id, resource_key)
);

CREATE TABLE auth.login_attempts (
	user_id int NOT NULL REFERENCES auth.users(id) ON DELETE CASCADE ON UPDATE CASCADE,
	remote_address varchar(45) NOT NULL, /* Supports IPv6 and possible IPv4 tunneling representation */
	date_occurred timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (user_id, remote_address, date_occurred)
);

/* DEFAULT DATA */

INSERT INTO auth.roles (name) VALUES('Administrator');
INSERT INTO auth.roles (name) VALUES('Member');

INSERT INTO auth.actions (name, bit_value) VALUES ('create',     1);
INSERT INTO auth.actions (name, bit_value) VALUES ('remove',     2);
INSERT INTO auth.actions (name, bit_value) VALUES ('update',     4);
INSERT INTO auth.actions (name, bit_value) VALUES ('manage',     8);
INSERT INTO auth.actions (name, bit_value) VALUES ('show',    2048);
