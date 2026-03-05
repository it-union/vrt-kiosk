<?php
declare(strict_types=1);

require_once __DIR__ . '/../modules/schedule_theme_repository.php';

$kioskDeviceKey = defined('KIOSK_DEVICE_KEY') ? (string)KIOSK_DEVICE_KEY : 'main-kiosk';
$pageTitle = 'Экран киоска';
$scheduleThemes = scheduleThemeLoadAll();
require __DIR__ . '/../views/kiosk/main.php';
