create table if not exists oauth_refresh_tokens
(
	refresh_token varchar(40) not null
		primary key,
	client_id varchar(80) not null,
	user_id varchar(255) null,
	expires timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
	scope varchar(2000) null
);

