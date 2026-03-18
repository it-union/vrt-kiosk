<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/app_settings.php';

requireAdministratorApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    $settings = appSettingsSaveKioskDisplay(dbMysql(), [
        'kiosk_width_px' => (int)($_POST['kiosk_width_px'] ?? 1920),
        'kiosk_height_px' => (int)($_POST['kiosk_height_px'] ?? 1080),
        'html_template_preview_tune_pct' => (int)($_POST['html_template_preview_tune_pct'] ?? 100),
        'client_media_cache_enabled' => (int)($_POST['client_media_cache_enabled'] ?? 1),
    ]);
    jsonResponse(['ok' => true, 'data' => $settings]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить настройки экрана'], 500);
}
