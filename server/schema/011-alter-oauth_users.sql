# TODO should probably actually be added to user_settings?
alter table oauth_users
    change `password` `password` varchar(2000)                       null comment 'Password hash for user',
    add created                  timestamp default current_timestamp not null,
    add modified                 timestamp default current_timestamp not null on update current_timestamp;
