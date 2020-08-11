create table if not exists users
(
	id int auto_increment
		primary key,
	username varchar(256) not null,
	password mediumtext null,
	created timestamp default CURRENT_TIMESTAMP not null,
	modified timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
	constraint users_username_uindex
		unique (username)
);

