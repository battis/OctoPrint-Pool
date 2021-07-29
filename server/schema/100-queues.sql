create table queues
(
	id int auto_increment
		primary key,
	user_id varchar(255) not null,
	name varchar(255) not null,
	description text null,
	comment text null,
	root text null,
	file_management_strategy mediumtext null,
	created timestamp default CURRENT_TIMESTAMP not null,
	modified timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
	`order` int null,
	constraint queues_oauth_users_fk
		foreign key (user_id) references oauth_users (username)
			on update cascade on delete cascade
);

