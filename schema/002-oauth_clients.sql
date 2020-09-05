create table if not exists oauth_clients
(
	client_id varchar(80) not null
		primary key,
	client_secret varchar(80) null,
	redirect_uri varchar(2000) null,
	grant_types varchar(80) null,
	scope varchar(100) null,
	user_id varchar(80) null
);

