<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/content_repository.php';
require_once __DIR__ . '/../modules/doctor_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$id = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;
$type = trim((string)($_POST['type'] ?? 'image'));
$title = trim((string)($_POST['title'] ?? ''));
$body = trim((string)($_POST['body'] ?? ''));
$mediaUrl = trim((string)($_POST['media_url'] ?? ''));
$dataJson = trim((string)($_POST['data_json'] ?? ''));
$isActive = isset($_POST['is_active']) ? ((int)$_POST['is_active'] === 1 ? 1 : 0) : 1;
$publishFrom = trim((string)($_POST['publish_from'] ?? ''));
$publishTo = trim((string)($_POST['publish_to'] ?? ''));

$allowedTypes = ['text', 'image', 'html', 'video', 'ppt', 'schedule'];
$scheduleDoctorId = 0;
if (!in_array($type, $allowedTypes, true)) {
    jsonResponse(['ok' => false, 'error' => 'Недопустимый type'], 400);
}
if ($title === '') {
    jsonResponse(['ok' => false, 'error' => 'Нужен title'], 400);
}
if ($type === 'image' && $mediaUrl === '') {
    jsonResponse(['ok' => false, 'error' => 'Для изображения нужен media_url'], 400);
}
if ($type === 'video' && $mediaUrl === '') {
    jsonResponse(['ok' => false, 'error' => 'Для видео нужен media_url'], 400);
}
if ($type === 'ppt' && $mediaUrl === '') {
    jsonResponse(['ok' => false, 'error' => 'PPT requires media_url'], 400);
}
if ($type === 'text' && $body === '') {
    jsonResponse(['ok' => false, 'error' => 'Для текста нужен body'], 400);
}
if ($type === 'html' && $body === '') {
    jsonResponse(['ok' => false, 'error' => 'Для HTML нужен body'], 400);
}
if ($type === 'schedule') {
    $scheduleData = $dataJson !== '' ? json_decode($dataJson, true) : [];
    $scheduleNode = is_array($scheduleData) && is_array($scheduleData['schedule'] ?? null) ? $scheduleData['schedule'] : [];
    $scheduleDoctorId = (int)($scheduleNode['doctor_id'] ?? 0);
    if ($scheduleDoctorId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Для расписания нужен doctor_id'], 400);
    }
}

function ensureContentTypeEnum(PDO $pdo, string $value): void
{
    $allowed = ['text', 'image', 'html', 'video', 'ppt', 'schedule'];
    if (!in_array($value, $allowed, true)) {
        return;
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM content_items LIKE 'type'");
    if (!$stmt) {
        return;
    }
    $row = $stmt->fetch();
    if (!is_array($row)) {
        return;
    }

    $typeRaw = (string)($row['Type'] ?? '');
    if (!preg_match('/^enum\((.*)\)$/i', $typeRaw, $m)) {
        return;
    }
    preg_match_all("/'([^']+)'/", $m[1], $matches);
    $values = $matches[1] ?? [];
    if (in_array($value, $values, true)) {
        return;
    }

    foreach ($allowed as $candidate) {
        if (!in_array($candidate, $values, true)) {
            $values[] = $candidate;
        }
    }
    $enumSql = implode(',', array_map(static fn(string $v): string => "'" . str_replace("'", "''", $v) . "'", $values));
    $pdo->exec("ALTER TABLE content_items MODIFY COLUMN type ENUM($enumSql) NOT NULL");
}

$dataJsonValue = null;
if ($dataJson !== '') {
    json_decode($dataJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(['ok' => false, 'error' => 'data_json должен быть корректным JSON'], 400);
    }
    $dataJsonValue = $dataJson;
}

$toSqlDateTime = static function (string $v): ?string {
    if ($v === '') {
        return null;
    }
    return str_replace('T', ' ', $v) . (strlen($v) === 16 ? ':00' : '');
};

$payload = [
    'type' => $type,
    'title' => $title,
    'body' => $body !== '' ? $body : null,
    'data_json' => $dataJsonValue,
    'media_url' => $mediaUrl !== '' ? $mediaUrl : null,
    'is_active' => $isActive,
    'publish_from' => $toSqlDateTime($publishFrom),
    'publish_to' => $toSqlDateTime($publishTo),
];

try {
    $pdo = dbMysql();
    $currentUserId = authCurrentUserId();
    ensureContentTypeEnum($pdo, $type);
    if ($type === 'schedule' && !doctorExists($pdo, $scheduleDoctorId)) {
        jsonResponse(['ok' => false, 'error' => 'Указанный врач не найден'], 400);
    }
    if ($id > 0) {
        $existing = contentGet($pdo, $id);
        if ($existing === null) {
            jsonResponse(['ok' => false, 'error' => 'Контент не найден'], 404);
        }
        if (!authCanManageOwnedEntity(isset($existing['created_by']) ? (int)$existing['created_by'] : null)) {
            jsonResponse(['ok' => false, 'error' => 'Недостаточно прав для редактирования контента'], 403);
        }
        $payload['updated_by'] = $currentUserId > 0 ? $currentUserId : null;
        $ok = contentUpdate($pdo, $id, $payload);
        if (!$ok) {
            jsonResponse(['ok' => false, 'error' => 'Контент не найден'], 404);
        }
        $contentId = $id;
    } else {
        $payload['created_by'] = $currentUserId > 0 ? $currentUserId : null;
        $payload['updated_by'] = $currentUserId > 0 ? $currentUserId : null;
        $contentId = contentCreate($pdo, $payload);
    }

    jsonResponse(['ok' => true, 'data' => ['content_id' => $contentId]]);
} catch (Throwable $e) {
    $msg = trim((string)$e->getMessage());
    if ($msg === '') {
        $msg = 'Не удалось сохранить контент';
    }
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить контент: ' . $msg], 500);
}
