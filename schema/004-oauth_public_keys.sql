create table if not exists oauth_public_keys
(
	client_id varchar(80) null,
	public_key varchar(8000) null,
	private_key varchar(8000) null,
	encryption_algorithm varchar(80) default 'RS256' null
);

