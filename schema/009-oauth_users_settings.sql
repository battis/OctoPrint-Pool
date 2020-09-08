create table if not exists oauth_users_settings
(
    id       int auto_increment
        primary key,
    user_id  varchar(255)                       not null,
    `key`    varchar(255)                       not null,
    value    text                               null,
    created  datetime default CURRENT_TIMESTAMP not null,
    modified datetime default CURRENT_TIMESTAMP null on update CURRENT_TIMESTAMP
);

