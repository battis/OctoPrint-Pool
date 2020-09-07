<?php


namespace Battis\OctoPrintPool;


use PDO;

trait UserSettings
{
    function getUserSetting(PDO $pdo, string $user_id, string $key, $default = null, callable $filter = null)
    {
        $value = $default;
        $select = $pdo->prepare("
            SELECT * FROM `oauth_users_settings`
                WHERE
                    `user_id` = :user_id AND
                    `key` = :key
                ORDER BY
                    modified DESC
                LIMIT 1
        ");
        if ($select->execute([
            'user_id' => $user_id,
            'key' => $key
        ])) {
            if ($setting = $select->fetch()) {
                $value = $setting['value'];
                if ($filter) {
                    $value = $filter($value);
                }
            }
        }
        return $value;
    }

    function forceBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
