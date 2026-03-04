<?php
declare(strict_types=1);

function appSettingsEnsureSchema(PDO $pdo): void
{
    static $ready = false;

    if ($ready) {
        return;
    }

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS `app_settings` (
            `setting_key` varchar(100) NOT NULL,
            `setting_value_json` json NOT NULL,
            `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`setting_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $ready = true;
}

function normalizeKioskDisplaySettings(?array $raw): array
{
    $src = is_array($raw) ? $raw : [];

    return [
        'kiosk_width_px' => max(640, min(7680, (int)($src['kiosk_width_px'] ?? 1920))),
        'kiosk_height_px' => max(360, min(4320, (int)($src['kiosk_height_px'] ?? 1080))),
        'html_template_preview_tune_pct' => max(25, min(400, (int)($src['html_template_preview_tune_pct'] ?? 100))),
    ];
}

function appSettingsGetJson(PDO $pdo, string $key, array $default = []): array
{
    appSettingsEnsureSchema($pdo);

    $stmt = $pdo->prepare('SELECT setting_value_json FROM app_settings WHERE setting_key = :setting_key LIMIT 1');
    $stmt->execute([':setting_key' => $key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!is_array($row)) {
        return $default;
    }

    $raw = json_decode((string)($row['setting_value_json'] ?? ''), true);
    return is_array($raw) ? $raw : $default;
}

function appSettingsSetJson(PDO $pdo, string $key, array $value): void
{
    appSettingsEnsureSchema($pdo);

    $payload = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($payload)) {
        throw new RuntimeException('app_settings_encode_failed');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO app_settings (setting_key, setting_value_json)
         VALUES (:setting_key, :setting_value_json)
         ON DUPLICATE KEY UPDATE
             setting_value_json = VALUES(setting_value_json),
             updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        ':setting_key' => $key,
        ':setting_value_json' => $payload,
    ]);
}

function appSettingsGetKioskDisplay(PDO $pdo): array
{
    return normalizeKioskDisplaySettings(appSettingsGetJson($pdo, 'kiosk_display', []));
}

function appSettingsSaveKioskDisplay(PDO $pdo, array $raw): array
{
    $normalized = normalizeKioskDisplaySettings($raw);
    appSettingsSetJson($pdo, 'kiosk_display', $normalized);
    return $normalized;
}
