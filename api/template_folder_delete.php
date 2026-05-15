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

$folderId = (int)($_POST['folder_id'] ?? 0);
if ($folderId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Folder id is required'], 400);
}

try {
    folderDeleteTemplate(dbMysql(), $folderId);
    jsonResponse(['ok' => true, 'data' => ['folder_id' => $folderId]]);
} catch (InvalidArgumentException $e) {
    jsonResponse(['ok' => false, 'error' => 'Некорректный идентификатор папки'], 400);
} catch (RuntimeException $e) {
    if ((string)$e->getMessage() === 'folder_not_found') {
        jsonResponse(['ok' => false, 'error' => 'Папка не найдена'], 404);
    }
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить папку шаблонов'], 500);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить папку шаблонов'], 500);
}
