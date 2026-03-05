<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../modules/schedule_theme_repository.php';

$pageTitle = 'Конструктор шаблонов';
$projectVersion = projectVersion();
$currentUser = authCurrentUser();
$scheduleThemes = scheduleThemeLoadAll();
require __DIR__ . '/../views/template/main.php';
