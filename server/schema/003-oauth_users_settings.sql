create table if not exists oauth_users_settings
(
    id       int auto_increment
        primary key,
    user_id  varchar(255)                       not null,
    `key`    varchar(255)                       not null,
    value    text                               null,
    created  datetime default current_timestamp not null,
    modified datetime default current_timestamp null on update current_timestamp
);
