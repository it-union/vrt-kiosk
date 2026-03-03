<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/screen_service.php';

$deviceKey = isset($_GET['device_key']) ? normalizeScreenDeviceKey((string)$_GET['device_key']) : '';
$screenId = isset($_GET['screen_id']) ? (int)$_GET['screen_id'] : 1;
if ($deviceKey !== '') {
    $screenId = publicScreenIdByDeviceKey($deviceKey);
} elseif ($screenId < 0) {
    jsonResponse(['ok' => false, 'error' => 'Некорректный screen_id'], 400);
}

$deviceKey = deviceKeyByPublicScreenId($screenId);

try {
    $payload = getScreenPayload(dbMysql(), $screenId);
    $payload['device_key'] = $deviceKey;
    jsonResponse(['ok' => true, 'data' => $payload]);
} catch (Throwable $e) {
    jsonResponse([
        'ok' => true,
        'data' => [
            'screen_id' => $screenId,
            'device_key' => $deviceKey,
            'source' => 'fallback',
            'template' => null,
            'screen_style' => [
                'mode' => 'color',
                'color' => '#ffffff',
                'image' => '',
                'size' => 'cover',
                'position' => 'center center',
                'repeat' => 'no-repeat',
            ],
            'blocks' => [[
                'id' => 0,
                'key' => 'error',
                'x_pct' => 0,
                'y_pct' => 0,
                'w_pct' => 100,
                'h_pct' => 100,
                'z_index' => 1,
                'content_mode' => 'dynamic_current',
                'content_type' => 'image',
                'content' => [
                    'id' => null,
                    'type' => 'image',
                    'title' => 'Сервис временно недоступен',
                    'body' => 'Ошибка доступа к базе данных. Включен резервный режим.',
                    'media_url' => null,
                    'data_json' => null,
                ],
                'style' => null,
            ]],
        ]
    ]);
}
