<?php
declare(strict_types=1);

$kioskDeviceKey = defined('KIOSK_DEVICE_KEY') ? (string)KIOSK_DEVICE_KEY : 'main-kiosk';
$pageTitle = 'Экран киоска';
require __DIR__ . '/../views/kiosk/main.php';
