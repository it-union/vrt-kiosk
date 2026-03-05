<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/screen_repository.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$deviceKey = isset($_POST['device_key']) ? normalizeScreenDeviceKey((string)$_POST['device_key']) : '';
if ($deviceKey === '') {
    $raw = file_get_contents('php://input');
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['device_key'])) {
            $deviceKey = normalizeScreenDeviceKey((string)$decoded['device_key']);
            if (!isset($_POST['app_version']) && isset($decoded['app_version'])) {
                $_POST['app_version'] = (string)$decoded['app_version'];
            }
            if (!isset($_POST['source']) && isset($decoded['source'])) {
                $_POST['source'] = (string)$decoded['source'];
            }
            if (!isset($_POST['template_id']) && isset($decoded['template_id'])) {
                $_POST['template_id'] = (string)$decoded['template_id'];
            }
        }
    }
}
if ($deviceKey === '') {
    jsonResponse(['ok' => false, 'error' => 'Не указан device_key'], 400);
}

$screenId = publicScreenIdByDeviceKey($deviceKey);
$appVersion = trim((string)($_POST['app_version'] ?? ''));
$source = trim((string)($_POST['source'] ?? ''));
$templateId = (int)($_POST['template_id'] ?? 0);

$payload = [
    'source' => $source,
    'template_id' => $templateId > 0 ? $templateId : null,
];

try {
    $pdo = dbMysql();
    $ipAddress = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    screenTouchHeartbeat($pdo, $screenId, $ipAddress !== '' ? $ipAddress : null, $appVersion, $payload);
    jsonResponse([
        'ok' => true,
        'data' => [
            'device_key' => $deviceKey,
            'screen_id' => $screenId,
            'server_time' => gmdate('c'),
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => trim((string)$e->getMessage()) ?: 'heartbeat_failed'], 500);
}

