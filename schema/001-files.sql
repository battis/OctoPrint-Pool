create table if not exists files
(
	id int auto_increment
		primary key,
	user int not null,
	filename mediumtext not null,
	path mediumtext not null,
	upload_user mediumtext null,
	comment mediumtext null,
	queued tinyint(1) default 1 not null,
	created timestamp default CURRENT_TIMESTAMP not null,
	modified timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
	constraint files_users_id_fk
		foreign key (user) references users (id)
);

