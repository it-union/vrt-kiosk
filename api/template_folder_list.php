<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/folder_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

try {
    jsonResponse(['ok' => true, 'data' => folderListTemplate(dbMysql())]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Failed to load template folders'], 500);
}
