<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/project.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../modules/activity_log_repository.php';
require_once __DIR__ . '/../modules/user_repository.php';

requireAdministratorRole();

$pdo = dbMysql();
activityLogEnsureSchema($pdo);
$projectVersion = projectVersion();
$pageTitle = 'Журнал активности';
$currentUser = authCurrentUser();
$users = activityLogGetUsers($pdo);
require __DIR__ . '/../views/activity_logs/main.php';