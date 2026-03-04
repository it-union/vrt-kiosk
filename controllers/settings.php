<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/app_settings.php';

$pageTitle = 'Настройки проекта';
$projectVersion = projectVersion();
$currentUser = authCurrentUser();
$kioskDisplaySettings = appSettingsGetKioskDisplay(dbMysql());

require __DIR__ . '/../views/settings/main.php';
