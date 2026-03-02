<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/template_service.php';
require_once __DIR__ . '/../modules/queue_repository.php';
require_once __DIR__ . '/../modules/screen_service.php';

$pageTitle = 'Панель администратора';
$projectVersion = projectVersion();
$currentUser = authCurrentUser();
$canManageAccounts = authHasRole(AUTH_ROLE_ADMINISTRATOR);
$canUseTemplateEditor = authHasRole(AUTH_ROLE_ADMINISTRATOR, AUTH_ROLE_EDITOR);
$pdo = dbMysql();
$allTemplates = listTemplatesWithActiveMark($pdo);
$workTemplates = array_values(array_filter($allTemplates, static function (array $row): bool {
    return (string)($row['status'] ?? '') === 'work';
}));
$activeQueue = queueGetActive($pdo);
$activeQueueItems = $activeQueue ? queueGetItems($pdo, (int)$activeQueue['id']) : [];
$screenPayload = getScreenPayload($pdo, 1);
$currentScreenSource = (string)($screenPayload['source'] ?? '');
$currentScreenTemplateId = (int)($screenPayload['template']['id'] ?? 0);

require __DIR__ . '/../views/admin/main.php';
