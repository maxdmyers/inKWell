CREATE SCHEMA inkwell;

CREATE TABLE inkwell.users (
	id serial PRIMARY KEY,
	username varchar(64) NOT NULL UNIQUE,
	login_password varchar(512) NOT NULL,
	avatar varchar(512) DEFAULT NULL,
	status varchar(16) NOT NULL DEFAULT 'Active' CHECK(status IN ('Active', 'Inactive', 'Disabled')),
	date_created timestamp DEFAULT CURRENT_TIMESTAMP,
	date_last_accessed timestamp DEFAULT NULL
);

CREATE TABLE inkwell.user_login_attempts (
	user_id integer NOT NULL REFERENCES inkwell.users(id) ON DELETE CASCADE ON UPDATE CASCADE,
	remote_address varchar(45) NOT NULL, /* Supports IPv6 and possible IPv4 tunneling representation */
	date_occurred timestamp DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (user_id, remote_address, date_occurred)
);

CREATE TABLE inkwell.auth_roles (
	id serial PRIMARY KEY,
	name varchar(32) UNIQUE NOT NULL
);

INSERT INTO inkwell.auth_roles (name) VALUES('Administrator');
INSERT INTO inkwell.auth_roles (name) VALUES('Member');

CREATE TABLE inkwell.user_auth_roles (
	user_id integer REFERENCES inkwell.users(id) ON DELETE CASCADE ON UPDATE CASCADE,
	auth_role_id integer REFERENCES inkwell.auth_roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
	PRIMARY KEY (user_id, auth_role_id)
);

CREATE TABLE inkwell.auth_actions (
	action_id serial PRIMARY KEY,
	name varchar(32) UNIQUE NOT NULL,
	bit_value integer UNIQUE NOT NULL
);

INSERT INTO inkwell.auth_actions (name, bit_value) VALUES ('create',     1);
INSERT INTO inkwell.auth_actions (name, bit_value) VALUES ('remove',     2);
INSERT INTO inkwell.auth_actions (name, bit_value) VALUES ('update',     4);
INSERT INTO inkwell.auth_actions (name, bit_value) VALUES ('manage',     8);
INSERT INTO inkwell.auth_actions (name, bit_value) VALUES ('show',    2048);

CREATE TABLE inkwell.user_permissions (
	user_id integer REFERENCES inkwell.users(id) ON DELETE CASCADE ON UPDATE CASCADE,
	resource_key varchar(2048) NOT NULL,
	bit_value integer NOT NULL,
	PRIMARY KEY (user_id, resource_key)
);

CREATE TABLE inkwell.auth_role_permissions (
	auth_role_id integer REFERENCES inkwell.auth_roles(id) ON DELETE CASCADE ON UPDATE CASCADE,
	resource_key varchar(2048) NOT NULL,
	bit_value integer NOT NULL,
	PRIMARY KEY (auth_role_id, resource_key)
);
