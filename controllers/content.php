<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';

$pageTitle = 'Панель контента';
$projectVersion = projectVersion();
require __DIR__ . '/../views/content/main.php';
