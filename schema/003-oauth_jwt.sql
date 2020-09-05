create table if not exists oauth_jwt
(
	client_id varchar(80) not null
		primary key,
	subject varchar(80) null,
	public_key varchar(2000) null
);

