<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/screen_service.php';

requirePanelApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$screenId = isset($_POST['screen_id']) ? (int)$_POST['screen_id'] : 0;
$templateId = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;

if ($screenId <= 0 || $templateId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужны screen_id и template_id'], 400);
}
$pdo = dbMysql();

try {
    $pdo->beginTransaction();
    $commandId = showTemplateNow($pdo, $screenId, $templateId);
    $pdo->commit();

    jsonResponse([
        'ok' => true,
        'data' => [
            'screen_id' => $screenId,
            'template_id' => $templateId,
            'command_id' => $commandId,
        ],
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getMessage() === 'template_not_found') {
        jsonResponse(['ok' => false, 'error' => 'Рабочий шаблон не найден'], 404);
    }

    jsonResponse(['ok' => false, 'error' => 'Не удалось включить ручной показ шаблона'], 500);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(['ok' => false, 'error' => 'Не удалось включить ручной показ шаблона'], 500);
}
