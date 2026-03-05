<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/doctor_repository.php';

requireAdministratorApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$rowId = isset($_POST['row_id']) ? (int)$_POST['row_id'] : 0;
$doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
$fullName = trim((string)($_POST['full_name'] ?? ''));
$isActive = isset($_POST['is_active']) ? (((int)$_POST['is_active']) === 1 ? 1 : 0) : 1;

if ($doctorId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Введите корректный ID доктора'], 400);
}
if ($fullName === '') {
    jsonResponse(['ok' => false, 'error' => 'Введите ФИО врача'], 400);
}

try {
    $pdo = dbMysql();
    if ($rowId > 0) {
        $ok = doctorUpdate($pdo, $rowId, $doctorId, $fullName, $isActive);
        if (!$ok) {
            jsonResponse(['ok' => false, 'error' => 'Врач не найден'], 404);
        }
        jsonResponse(['ok' => true, 'data' => ['row_id' => $rowId, 'doctor_id' => $doctorId]]);
    }

    $newId = doctorCreate($pdo, $doctorId, $fullName, $isActive);
    jsonResponse(['ok' => true, 'data' => ['row_id' => $newId, 'doctor_id' => $doctorId]]);
} catch (PDOException $e) {
    if ((string)$e->getCode() === '23000') {
        jsonResponse(['ok' => false, 'error' => 'Доктор с таким ID уже существует'], 409);
    }
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить врача'], 500);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить врача'], 500);
}
