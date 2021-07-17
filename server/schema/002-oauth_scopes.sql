create table if not exists oauth_scopes
(
    scope      varchar(255) null
        unique,
    is_default tinyint(1)   null
);

