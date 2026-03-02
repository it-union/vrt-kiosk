<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/template_service.php';
require_once __DIR__ . '/../modules/queue_repository.php';

$pageTitle = 'Очередь показа';
$projectVersion = projectVersion();
$currentUser = authCurrentUser();
$pdo = dbMysql();
queueEnsureSchema($pdo);
$activeQueue = queueGetActive($pdo);
$queues = queueListAll($pdo);
$workTemplates = array_values(array_filter(listTemplatesWithActiveMark($pdo), static function (array $row): bool {
    return (string)($row['status'] ?? '') === 'work';
}));

require __DIR__ . '/../views/queue/main.php';
