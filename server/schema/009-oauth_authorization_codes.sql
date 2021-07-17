create table if not exists oauth_authorization_codes
(
    authorization_code varchar(40)                         not null
        primary key,
    client_id          varchar(80)                         not null,
    user_id            varchar(255)                        null,
    redirect_uri       varchar(2000)                       null,
    expires            timestamp default current_timestamp not null on update current_timestamp,
    scope              varchar(2000)                       null
);
