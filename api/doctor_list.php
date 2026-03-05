<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/doctor_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    $pdo = dbMysql();
    $items = doctorListActive($pdo);
    jsonResponse(['ok' => true, 'data' => ['items' => $items]]);
} catch (Throwable $e) {
    $msg = trim((string)$e->getMessage());
    if ($msg === '') {
        $msg = 'Не удалось загрузить список врачей';
    }
    jsonResponse(['ok' => false, 'error' => $msg], 500);
}

