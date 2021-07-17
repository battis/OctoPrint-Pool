# oauth tables cascade deletes
alter table oauth_users_settings
    add constraint setting_user_fk
        foreign key (user_id)
            references oauth_users (username)
            on update cascade
            on delete cascade;

alter table oauth_clients
    add constraint client_user_fk
        foreign key (user_id)
            references oauth_users (username)
            on update cascade
            on delete cascade;

alter table oauth_jwt
    add constraint jwt_client
        foreign key (client_id)
            references oauth_clients (client_id)
            on update cascade
            on delete cascade;

alter table oauth_public_keys
    add constraint public_key_client_fk
        foreign key (client_id)
            references oauth_clients (client_id)
            on update cascade
            on delete cascade;

alter table oauth_access_tokens
    add constraint access_token_client_fk
        foreign key (client_id)
            references oauth_clients (client_id)
            on update cascade
            on delete cascade,
    add constraint access_token_user_fk
        foreign key (user_id)
            references oauth_users (username)
            on update cascade
            on delete cascade;

alter table oauth_refresh_tokens
    add constraint refresh_token_client_fk
        foreign key (client_id)
            references oauth_clients (client_id)
            on update cascade
            on delete cascade,
    add constraint refresh_token_user
        foreign key (user_id)
            references oauth_users (username)
            on update cascade
            on delete cascade;

alter table oauth_authorization_codes
    add constraint authorization_code_client_fk
        foreign key (client_id)
            references oauth_clients (client_id)
            on update cascade
            on delete cascade,
    add constraint authorization_code_user_fk
        foreign key (user_id)
            references oauth_users (username)
            on update cascade
            on delete cascade;
