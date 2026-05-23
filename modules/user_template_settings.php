<?php
declare(strict_types=1);

function userTemplateSettingsEnsureSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `user_template_settings` (
            `user_id` BIGINT(20) UNSIGNED NOT NULL,
            `settings_json` JSON NOT NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`user_id`),
            CONSTRAINT `fk_user_template_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $ready = true;
}

function normalizeUserTemplateSettings(?array $raw): array
{
    $src = is_array($raw) ? $raw : [];

    $showGrid = ((int)($src['show_grid'] ?? 0)) === 1 ? 1 : 0;
    $snapToGrid = ((int)($src['snap_to_grid'] ?? 0)) === 1 ? 1 : 0;
    if ($showGrid !== 1) {
        $snapToGrid = 0;
    }

    return [
        'show_content_preview' => ((int)($src['show_content_preview'] ?? 0)) === 1 ? 1 : 0,
        'disable_preview_animation' => ((int)($src['disable_preview_animation'] ?? 0)) === 1 ? 1 : 0,
        'show_grid' => $showGrid,
        'snap_to_grid' => $snapToGrid,
    ];
}

function userTemplateSettingsGet(PDO $pdo, int $userId): array
{
    userTemplateSettingsEnsureSchema($pdo);
    if ($userId <= 0) {
        return normalizeUserTemplateSettings([]);
    }

    $stmt = $pdo->prepare('SELECT settings_json FROM user_template_settings WHERE user_id = :user_id LIMIT 1');
    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
        return normalizeUserTemplateSettings([]);
    }

    $decoded = json_decode((string)($row['settings_json'] ?? ''), true);
    return normalizeUserTemplateSettings(is_array($decoded) ? $decoded : []);
}

function userTemplateSettingsSave(PDO $pdo, int $userId, array $raw): array
{
    userTemplateSettingsEnsureSchema($pdo);
    if ($userId <= 0) {
        throw new RuntimeException('invalid_user_id');
    }

    $normalized = normalizeUserTemplateSettings($raw);
    $payload = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($payload)) {
        throw new RuntimeException('user_template_settings_encode_failed');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO user_template_settings (user_id, settings_json)
         VALUES (:user_id, :settings_json)
         ON DUPLICATE KEY UPDATE
             settings_json = VALUES(settings_json),
             updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':settings_json' => $payload,
    ]);

    return $normalized;
}

