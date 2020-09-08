create table if not exists oauth_scopes
(
    scope      text       null,
    is_default tinyint(1) null
);

