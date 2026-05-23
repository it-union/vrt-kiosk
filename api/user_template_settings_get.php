<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/user_template_settings.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    $pdo = dbMysql();
    $userId = authCurrentUserId();
    $settings = userTemplateSettingsGet($pdo, $userId);
    jsonResponse(['ok' => true, 'data' => $settings]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось загрузить настройки шаблонизатора'], 500);
}

