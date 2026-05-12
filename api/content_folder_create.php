<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/folder_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Method not allowed'], 405);
}

$name = trim((string)($_POST['name'] ?? ''));
if ($name === '') {
    jsonResponse(['ok' => false, 'error' => 'Folder name is required'], 400);
}

try {
    $id = folderCreateContent(dbMysql(), $name);
    jsonResponse(['ok' => true, 'data' => ['folder_id' => $id]]);
} catch (PDOException $e) {
    $sqlState = (string)($e->getCode() ?? '');
    if ($sqlState === '23000') {
        jsonResponse(['ok' => false, 'error' => 'Папка с таким названием уже существует'], 409);
    }
    jsonResponse(['ok' => false, 'error' => 'Ошибка базы данных при создании папки'], 500);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Failed to create content folder'], 500);
}
