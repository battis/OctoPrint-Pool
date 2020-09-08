create table if not exists oauth_users
(
    username     varchar(255)  not null
        primary key,
    password     varchar(2000) null,
    display_name varchar(255)  null
);

