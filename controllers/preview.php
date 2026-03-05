<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';
require_once __DIR__ . '/../modules/schedule_theme_repository.php';

$pageTitle = 'Предпросмотр шаблона';
$projectVersion = projectVersion();
$scheduleThemes = scheduleThemeLoadAll();
require __DIR__ . '/../views/preview/main.php';
