<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';
require_once __DIR__ . '/../core/auth.php';

$pageTitle = 'Конструктор шаблонов';
$projectVersion = projectVersion();
$currentUser = authCurrentUser();
require __DIR__ . '/../views/template/main.php';
