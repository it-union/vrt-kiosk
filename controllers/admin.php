<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';

$pageTitle = 'Панель администратора';
$projectVersion = projectVersion();
require __DIR__ . '/../views/admin/main.php';
