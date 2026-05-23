<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/user_template_settings.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    $pdo = dbMysql();
    $userId = authCurrentUserId();
    $settings = userTemplateSettingsSave($pdo, $userId, [
        'show_content_preview' => (int)($_POST['show_content_preview'] ?? 0),
        'disable_preview_animation' => (int)($_POST['disable_preview_animation'] ?? 0),
        'show_grid' => (int)($_POST['show_grid'] ?? 0),
        'snap_to_grid' => (int)($_POST['snap_to_grid'] ?? 0),
    ]);
    jsonResponse(['ok' => true, 'data' => $settings]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить настройки шаблонизатора'], 500);
}

