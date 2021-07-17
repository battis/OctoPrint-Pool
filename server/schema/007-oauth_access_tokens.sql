create table if not exists oauth_access_tokens
(
    access_token varchar(40)                         not null
        primary key,
    client_id    varchar(80)                         not null,
    user_id      varchar(255)                        null,
    expires      timestamp default current_timestamp not null on update current_timestamp,
    scope        varchar(2000)                       null
);
