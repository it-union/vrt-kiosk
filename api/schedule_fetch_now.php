<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/doctor_repository.php';
require_once __DIR__ . '/../modules/schedule_cache_service.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
if ($doctorId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Некорректный doctor_id'], 400);
}
$point = isset($_POST['point']) ? (int)$_POST['point'] : 0;
if (!in_array($point, [0, 1], true)) {
    $point = 0;
}
$days = isset($_POST['days']) ? (int)$_POST['days'] : 7;
$days = max(1, min(31, $days));

try {
    $pdo = dbMysql();
    if (!doctorExists($pdo, $doctorId)) {
        jsonResponse(['ok' => false, 'error' => 'Доктор не найден или неактивен'], 400);
    }

    $apiConfig = scheduleApiLoadConfig();
    $payload = scheduleFetchForDoctorId($apiConfig, $doctorId, $point, $days);
    $updatedAt = gmdate('c');

    jsonResponse([
        'ok' => true,
        'data' => [
            'doctor_id' => $doctorId,
            'point' => $point,
            'days' => $days,
            'payload' => $payload,
            'updated_at' => $updatedAt,
        ],
    ]);
} catch (Throwable $e) {
    $message = trim((string)$e->getMessage());
    if ($message === '') {
        $message = 'Не удалось получить данные расписания';
    }
    jsonResponse(['ok' => false, 'error' => $message], 500);
}
