create table if not exists files
(
    id       int auto_increment
        primary key,
    user_id  varchar(255)                         not null,
    filename mediumtext                           not null,
    path     mediumtext                           not null,
    tags     mediumtext                           null,
    comment  mediumtext                           null,
    queued   tinyint(1) default 1                 not null,
    created  timestamp  default CURRENT_TIMESTAMP not null,
    modified timestamp  default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP
);

create index files_oauth_users_username_fk
    on files (user_id);

