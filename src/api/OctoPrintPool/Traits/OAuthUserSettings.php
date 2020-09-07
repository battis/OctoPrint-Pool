<?php


namespace Battis\OctoPrintPool\Traits;


trait OAuthUserSettings
{
    use PdoStorage, OAuthUserId;

    private function getUserSetting(string $key, $default = null, callable $filter = null)
    {
        $value = $default;
        if (false === (empty($this->oauthUserId) || empty($this->pdo))) {
            $select = $this->pdo->prepare("
            SELECT * FROM `oauth_users_settings`
                WHERE
                    `user_id` = :user_id AND
                    `key` = :key
                ORDER BY
                    modified DESC
                LIMIT 1
        ");
            if ($select->execute([
                'user_id' => $this->oauthUserId,
                'key' => $key
            ])) {
                if ($setting = $select->fetch()) {
                    $value = $setting['value'];
                    if ($filter) {
                        $value = $filter($value);
                    }
                }
            }
        }
        // TODO other types that should maybe be handled here?
        if (is_bool($default)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        return $value;
    }
}
