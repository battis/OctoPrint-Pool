create table files
(
	id int auto_increment
		primary key,
	user_id varchar(255) not null,
	queue_id int not null,
	filename mediumtext not null,
	path mediumtext not null,
	tags mediumtext null,
	comment mediumtext null,
	queued tinyint(1) default 1 not null,
	available tinyint(1) default 1 null,
	created timestamp default CURRENT_TIMESTAMP not null,
	modified timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
	dequeued timestamp null,
	`order` int null,
	constraint files_oauth_users_fk
		foreign key (user_id) references oauth_users (username)
			on update cascade on delete cascade,
	constraint files_queues_fk
		foreign key (queue_id) references queues (id)
			on update cascade on delete cascade
);

