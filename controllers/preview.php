<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/project.php';

$pageTitle = 'Предпросмотр шаблона';
$projectVersion = projectVersion();
require __DIR__ . '/../views/preview/main.php';
