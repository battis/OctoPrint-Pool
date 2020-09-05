create table if not exists oauth_users
(
	username varchar(255) not null
		primary key,
	password varchar(2000) null,
	first_name varchar(255) null,
	last_name varchar(255) null
);

